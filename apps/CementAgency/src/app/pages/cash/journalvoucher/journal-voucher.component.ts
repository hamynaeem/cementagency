import { Component, OnInit, ViewChild } from "@angular/core";
import { Router } from "@angular/router";
import { JSON2Date, GetDateJSON, getCurDate } from "../../../factories/utilities";
import { HttpBase } from "../../../services/httpbase.service";
import { MyToastService } from "../../../services/toaster.server";
import { VoucherModel } from "../voucher.model";

@Component({
  selector: "app-journal-voucher",
  templateUrl: "./journal-voucher.component.html",
  styleUrls: ["./journal-voucher.component.scss"],
})
export class JournalvoucherComponent implements OnInit {
  @ViewChild("cmbCustomer") cmbCustomer: any;
  public Voucher = new VoucherModel();
  public Voucher1 = new VoucherModel();
  Customers = [];
  Customers1 = [];
  AcctTypes = [];
  AcctTypeID = "";
  AcctTypeID1 = "";
  VouchersList: object[] = [];
  curCustomer: any = {};
  curCustomer1: any = {};

  voucher: any;
  voucher1: any;
  Products: any = [];
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router
  ) { }

  ngOnInit() {
    // Set initial dates
    this.Voucher.Date = GetDateJSON();
    this.Voucher1.Date = GetDateJSON();
    
    this.http.getData("accttypes").then((r: any) => {
      this.AcctTypes = r;
    });
    this.http.ProductsAll().then((r: any) => {
      r.unshift({ProductID: '', ProductName: 'All Products'});
      this.Products = r;
    });
  }

  LoadCustomer(event: any, v: number) {
    if (event.itemData.AcctTypeID !== "") {
      this.http
        .getData(
          "qrycustomers?flds=CustomerName,Address, Balance, CustomerID&orderby=CustomerName" +
          "&filter=AcctTypeID=" +
          event.itemData.AcctTypeID
        )
        .then((r: any) => {
          if (v == 1)
            this.Customers = r;
          else
            this.Customers1 = r;
        });
    }
  }
  SaveData() {
    // Validate required fields for both vouchers
    if (!this.Voucher.CustomerID || this.Voucher.CustomerID === '') {
      this.alert.Error('Please select an account for the first voucher', 'Validation Error');
      return;
    }

    if (!this.Voucher1.CustomerID || this.Voucher1.CustomerID === '') {
      this.alert.Error('Please select an account for the second voucher', 'Validation Error');
      return;
    }

    if ((!this.Voucher.Debit || this.Voucher.Debit <= 0) && (!this.Voucher.Credit || this.Voucher.Credit <= 0)) {
      this.alert.Error('Please enter a valid amount for the first voucher', 'Validation Error');
      return;
    }

    if ((!this.Voucher1.Debit || this.Voucher1.Debit <= 0) && (!this.Voucher1.Credit || this.Voucher1.Credit <= 0)) {
      this.alert.Error('Please enter a valid amount for the second voucher', 'Validation Error');
      return;
    }

    // Prepare voucher data
    this.Voucher.Date = JSON2Date(this.Voucher.Date);
    this.Voucher1.Date = JSON2Date(this.Voucher1.Date);
    this.Voucher.BusinessID = this.http.getBusinessID();
    this.Voucher1.BusinessID = this.http.getBusinessID();
    this.Voucher.PrevBalance = this.curCustomer.Balance || 0;
    this.Voucher1.PrevBalance = this.curCustomer1.Balance || 0;

    console.log('Saving voucher 1:', JSON.stringify(this.Voucher, null, 2));
    console.log('Saving voucher 2:', JSON.stringify(this.Voucher1, null, 2));

    // Save both vouchers
    this.saveBothVouchers();
  }

  private async saveBothVouchers() {
    try {
      console.log('Starting voucher save process...');
      
      // Prepare simplified voucher data that matches database structure exactly
      const voucher1Data = this.createSimplifiedVoucher(this.Voucher);
      const voucher2Data = this.createSimplifiedVoucher(this.Voucher1);
      
      console.log('Voucher 1 data:', voucher1Data);
      console.log('Voucher 2 data:', voucher2Data);

      // Save first voucher using postData (most reliable for new records)
      console.log('Saving first voucher...');
      const result1 = await this.http.postData('vouchers', voucher1Data);
      console.log('First voucher saved successfully:', result1);

      // Save second voucher
      console.log('Saving second voucher...');
      const result2 = await this.http.postData('vouchers', voucher2Data);
      console.log('Second voucher saved successfully:', result2);

      // Success
      this.alert.Sucess("Journal Voucher Saved Successfully", "Success", 1);
      this.resetForm();
      
    } catch (error: any) {
      console.error('Voucher save failed:', error);
      this.handleSaveError(error);
    }
  }

  private createSimplifiedVoucher(voucher: any) {
    const currentUser = this.http.geBranchData();
    console.log('Current user data:', currentUser);
    
    // Create voucher data matching exact database structure
    const voucherData = {
      Date: this.formatDateForDB(voucher.Date),
      AcctType: voucher.AcctTypeID ? String(voucher.AcctTypeID) : null,
      CustomerID: String(voucher.CustomerID),
      Description: String(voucher.Description || 'Journal Entry'),
      Debit: String(Number(voucher.Debit) || 0),
      Credit: String(Number(voucher.Credit) || 0),
      RefID: String(voucher.RefID || 0),
      RefType: String(1),
      FinYearID: String(currentUser.finyearid || 1),
      IsPosted: String(0),
      BusinessID: String(this.http.getBusinessID() || 1)
    };
    
    console.log('Created simplified voucher:', voucherData);
    return voucherData;
  }

  private formatDateForDB(dateInput: any): string {
    try {
      let dateStr;
      
      if (typeof dateInput === 'string') {
        dateStr = dateInput;
      } else if (dateInput && typeof dateInput === 'object' && dateInput.year) {
        // NgbDate format
        dateStr = `${dateInput.year}-${String(dateInput.month).padStart(2, '0')}-${String(dateInput.day).padStart(2, '0')}`;
      } else if (dateInput instanceof Date) {
        dateStr = dateInput.toISOString().split('T')[0];
      } else {
        // Fallback to current date
        dateStr = new Date().toISOString().split('T')[0];
      }
      
      // Validate date format (YYYY-MM-DD)
      const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
      if (!dateRegex.test(dateStr)) {
        throw new Error('Invalid date format');
      }
      
      return dateStr;
    } catch (error) {
      console.warn('Date formatting error:', error);
      return new Date().toISOString().split('T')[0]; // Fallback to current date
    }
  }

  private handleSaveError(error: any) {
    let errorMessage = 'Failed to save journal voucher. ';
    
    if (error.status === 500) {
      errorMessage += '\\n\\n🔧 SERVER ERROR DETECTED:\\n';
      errorMessage += '• The backend server encountered an internal error\\n';
      errorMessage += '• This is usually caused by database configuration issues\\n';
      errorMessage += '• Please check that your MySQL/database server is running\\n';
      errorMessage += '• Verify the database "db_cement" exists and is accessible\\n\\n';
      
      errorMessage += '📋 TEMPORARY SOLUTION:\\n';
      errorMessage += '• Check the browser console for detailed error logs\\n';
      errorMessage += '• Contact your system administrator to check:\\n';
      errorMessage += '  - Database server status\\n';
      errorMessage += '  - Database connection credentials\\n';
      errorMessage += '  - Missing database tables or permissions\\n\\n';
      
      errorMessage += '💡 QUICK FIXES TO TRY:\\n';
      errorMessage += '• Restart your XAMPP/WAMP MySQL service\\n';
      errorMessage += '• Check if database "db_cement" exists\\n';
      errorMessage += '• Verify database user has proper permissions\\n\\n';
      
      errorMessage += 'Technical details: ' + (error.message || 'Internal Server Error');
    } else if (error.error) {
      if (typeof error.error === 'string') {
        errorMessage += error.error;
      } else if (error.error.message) {
        errorMessage += error.error.message;
      } else {
        errorMessage += JSON.stringify(error.error);
      }
    } else if (error.message) {
      errorMessage += error.message;
    }
    
    this.alert.Error(errorMessage, 'Save Failed');
  }

  private tryAlternativeSave(voucher1Data: any, voucher2Data: any) {
    // Since all API endpoints are blocked by the missing stored procedure,
    // provide manual SQL solution immediately
    console.log('API endpoints blocked by database issue. Generating manual SQL...');
    this.tryManualSave(voucher1Data, voucher2Data);
  }

  private tryManualSave(voucher1Data: any, voucher2Data: any) {
    // Generate and display manual SQL statements
    const sql1 = this.generateInsertSQL('vouchers', voucher1Data);
    const sql2 = this.generateInsertSQL('vouchers', voucher2Data);
    
    console.log('\n=================================================================');
    console.log('MANUAL DATABASE SAVE REQUIRED');  
    console.log('=================================================================');
    console.log('The backend database is missing the sp_ManageCashbook stored procedure.');
    console.log('Please copy and execute these SQL statements directly in your database:');
    console.log('');
    console.log('-- First Voucher (Debit):');
    console.log(sql1);
    console.log('');
    console.log('-- Second Voucher (Credit):');  
    console.log(sql2);
    console.log('');
    console.log('=================================================================');
    console.log('INSTRUCTIONS:');
    console.log('1. Open phpMyAdmin or your database management tool');
    console.log('2. Select the "db_cement" database');
    console.log('3. Go to SQL tab');
    console.log('4. Copy and paste the above SQL statements');
    console.log('5. Click "Go" to execute');
    console.log('=================================================================\n');
    
    // Also create a downloadable text file with the SQL
    const sqlContent = `-- Journal Voucher SQL Statements
-- Generated on: ${new Date().toLocaleString()}
-- 
-- INSTRUCTIONS:
-- 1. Open phpMyAdmin or your database management tool
-- 2. Select the "db_cement" database  
-- 3. Copy and paste these SQL statements
-- 4. Execute them to save your journal voucher

-- First Voucher (Debit Entry):
${sql1}

-- Second Voucher (Credit Entry):  
${sql2}

-- END OF SQL STATEMENTS
`;
    
    // Create downloadable file
    const blob = new Blob([sqlContent], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `journal-voucher-${new Date().getTime()}.sql`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    this.alert.Error(
      `Automatic save failed due to missing database procedure (sp_ManageCashbook). 
      
📋 SOLUTION: Check your browser console for SQL statements to run manually in your database.
      
💾 A downloadable SQL file has been created for you.
      
🔧 PERMANENT FIX: Ask your IT team to create the missing "sp_ManageCashbook" stored procedure in the "db_cement" database.`,
      'Database Configuration Required'
    );
  }

  private generateInsertSQL(table: string, data: any): string {
    const fields = Object.keys(data).join(', ');
    const values = Object.values(data).map(val => `'${val}'`).join(', ');
    return `INSERT INTO ${table} (${fields}) VALUES (${values});`;
  }

  private objectToQueryString(obj: any): string {
    return Object.keys(obj)
      .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(obj[key])}`)
      .join('&');
  }


  private saveWithPostTask(voucher1Data: any, voucher2Data: any) {
    this.http.postTask("vouchers", voucher1Data)
      .then((r: any) => {
        console.log('PostTask - First voucher saved:', r);
        voucher2Data.RefID = r.id || r.VoucherID || '1';
        return this.http.postTask("vouchers", voucher2Data);
      })
      .then((r1) => {
        console.log('PostTask - Second voucher saved:', r1);
        this.alert.Sucess("Journal Voucher Saved Successfully", "Success", 1);
        this.resetForm();
      })
      .catch((err) => {
        console.error('Both methods failed:', err);
        let errorMsg = 'Unable to save journal voucher. ';
        if (err.error && typeof err.error === 'string' && err.error.includes('sp_ManageCashbook')) {
          errorMsg += 'Database stored procedure missing. Please contact IT support to create sp_ManageCashbook procedure.';
        } else {
          errorMsg += 'Please try again or contact support.';
        }
        this.alert.Error(errorMsg, 'Save Error');
      });
  }

  private resetForm() {
    this.Voucher = new VoucherModel();
    this.Voucher1 = new VoucherModel();
    this.curCustomer = {};
    this.curCustomer1 = {};
    this.AcctTypeID = '';
    this.AcctTypeID1 = '';
    this.Customers = [];
    this.Customers1 = [];
    
    // Set default date
    this.Voucher.Date = GetDateJSON();
    this.Voucher1.Date = GetDateJSON();
    
    if (this.cmbCustomer) {
      this.cmbCustomer.focusIn();
    }
  }

  GetCustomer(e: string, v: number) {
    console.log(e);
    if (e !== "") {
      this.http
        .getData("qrycustomers?filter=CustomerID=" + e)
        .then((r: any) => {
          if (v == 1)
            this.curCustomer = r[0];
          else
            this.curCustomer1 = r[0];
        });
    }
  }
  Round(amnt: number): number {
    return Math.round(amnt);
  }




}
