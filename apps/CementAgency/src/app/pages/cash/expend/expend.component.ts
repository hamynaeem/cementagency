import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';

class ExpenseModel {
  Date: any = GetDateJSON();
  HeadID = '';
  Desc = '';
  Amount = 0;
}
@Component({
  selector: 'app-expend',
  templateUrl: './expend.component.html',
  styleUrls: ['./expend.component.scss'],
})
export class ExpendComponent implements OnInit {
  @ViewChild('cmbHeads') cmbHeads!: ElementRef;
  public Voucher = new ExpenseModel();
  ExpenseHeads: Array<{ HeadID: number; Head: string; AcctType?: string; Balance?: number }> = [];
  AccountTypes: any[] = [];
  EditID = '';
  curCustomer: any = {};
  showAddModal = false;
  newCategory = { name: '', type: 'Expense' };
  showExpensesList = false;
  expensesList: any[] = [];
  loadingExpenses = false;
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private activatedRoute: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit() {
    console.log('Loading account names...');
    
    // Load account types first
    this.http.getData('accttypes').then((types: any) => {
      this.AccountTypes = types;
      console.log('Account types loaded:', types);
    });
    
    // Load all customer accounts to show in expense dropdown
    this.http.getData('qrycustomers?flds=CustomerID,CustomerName,AcctTypeID,Balance&orderby=CustomerName').then((accounts: any) => {
      console.log('Customer accounts loaded:', accounts);
      
      if (accounts && accounts.length > 0) {
        // Transform accounts to ExpenseHeads format
        this.ExpenseHeads = accounts.map((account: any) => ({
          HeadID: account.CustomerID,
          Head: account.CustomerName,
          AcctType: this.getAccountTypeName(account.AcctTypeID),
          Balance: account.Balance || 0
        }));
        
        // Add common expense accounts at the top
        const commonExpenses = [
          { HeadID: 999001, Head: '🏢 Office Expenses', AcctType: 'Firm', Balance: 0 },
          { HeadID: 999002, Head: '🚗 Travel & Transportation', AcctType: 'Firm', Balance: 0 },
          { HeadID: 999003, Head: '⚡ Utilities (Electric, Gas, Water)', AcctType: 'Firm', Balance: 0 },
          { HeadID: 999004, Head: '🏠 Rent & Maintenance', AcctType: 'Firm', Balance: 0 },
          { HeadID: 999005, Head: '📱 Communication (Phone, Internet)', AcctType: 'Firm', Balance: 0 },
          { HeadID: 999006, Head: '💼 Miscellaneous Expenses', AcctType: 'Firm', Balance: 0 }
        ];
        
        this.ExpenseHeads = [...commonExpenses, ...this.ExpenseHeads];
        
        console.log('Final ExpenseHeads with account names:', this.ExpenseHeads);
      } else {
        console.log('No customer accounts found, using default expense heads...');
        this.setDefaultExpenseHeads();
      }
    }).catch((err) => {
      console.error('Error loading customer accounts:', err);
      this.setDefaultExpenseHeads();
    });

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.http
          .getData('expend/' + this.EditID)
          .then((r: any) => {
            if (r) {
              this.Voucher = r;
              this.Voucher.Date = GetDateJSON(new Date(r.Date));
            }
          }).catch((err) => {
            this.alert.Error('Not found', 'Error', 1);
          });
      } else {
        this.EditID = '';
      }
    });
  }

  private getAccountTypeName(typeId: number): string {
    const type = this.AccountTypes.find(t => t.AcctTypeID == typeId);
    return type ? type.AcctType : 'Unknown';
  }

  private setDefaultExpenseHeads() {
    this.ExpenseHeads = [
      { HeadID: 1, Head: '🏢 Office Supplies & Stationery', AcctType: 'Expense', Balance: 0 },
      { HeadID: 2, Head: '🚗 Travel & Transportation', AcctType: 'Expense', Balance: 0 },
      { HeadID: 3, Head: '⚡ Utilities (Electric, Gas, Water)', AcctType: 'Expense', Balance: 0 },
      { HeadID: 4, Head: '🏠 Rent & Building Maintenance', AcctType: 'Expense', Balance: 0 },
      { HeadID: 5, Head: '🔧 Equipment & Machinery Maintenance', AcctType: 'Expense', Balance: 0 },
      { HeadID: 6, Head: '📱 Communication (Phone, Internet)', AcctType: 'Expense', Balance: 0 },
      { HeadID: 7, Head: '📝 Legal & Professional Fees', AcctType: 'Expense', Balance: 0 },
      { HeadID: 8, Head: '🚛 Freight & Transportation', AcctType: 'Expense', Balance: 0 },
      { HeadID: 9, Head: '💼 Miscellaneous Expenses', AcctType: 'Expense', Balance: 0 }
    ];
    console.log('Using default expense heads:', this.ExpenseHeads);
  }
  SaveData() {
    // Validate required fields
    if (!this.Voucher.HeadID || this.Voucher.HeadID === '') {
      this.alert.Error('Please select an expense head/account', 'Validation Error');
      return;
    }
    
    if (!this.Voucher.Amount || this.Voucher.Amount <= 0) {
      this.alert.Error('Please enter a valid amount', 'Validation Error');
      return;
    }
    
    if (!this.Voucher.Desc || this.Voucher.Desc.trim() === '') {
      this.alert.Error('Please enter a description for this expense', 'Validation Error');
      return;
    }

    // Create the expense as a voucher entry (which we know works)
    const voucherData = {
      Date: this.formatDateForDB(this.Voucher.Date),
      CustomerID: String(this.Voucher.HeadID), // Use HeadID as CustomerID 
      Description: String(this.Voucher.Desc.trim()),
      Debit: String(Number(this.Voucher.Amount)), // Expense = Debit
      Credit: String(0), // No credit for expenses
      RefID: String(0),
      RefType: String(4), // Type 4 for expenses
      FinYearID: String(1),
      IsPosted: String(0),
      BusinessID: String(this.http.getBusinessID() || 1)
    };
    
    console.log('Saving expense as voucher:', voucherData);

    // Save using the working voucher endpoint
    this.http.postData('vouchers', voucherData)
      .then((r) => {
        console.log('Expense saved successfully as voucher:', r);
        this.alert.Sucess('Expense Saved Successfully!', 'Success', 1);
        this.resetForm();
        
        // Refresh expenses list if it's currently shown
        if (this.showExpensesList) {
          this.loadExpensesList();
        }
      })
      .catch((error) => {
        console.error('Voucher method failed, trying direct expend method:', error);
        
        // Fallback: Try the original expend endpoint without IsPosted
        const simpleExpendData = {
          Date: this.formatDateForDB(this.Voucher.Date),
          HeadID: Number(this.Voucher.HeadID),
          Desc: this.Voucher.Desc.trim(),
          Amount: Number(this.Voucher.Amount)
        };
        
        console.log('Trying simple expend save:', simpleExpendData);
        
        this.http.postData('expend' + (this.EditID ? '/' + this.EditID : ''), simpleExpendData)
          .then((r) => {
            console.log('Expense saved via direct method:', r);
            this.alert.Sucess('Expense Saved Successfully!', 'Success', 1);
            this.resetForm();
            
            // Refresh expenses list if it's currently shown
            if (this.showExpensesList) {
              this.loadExpensesList();
            }
          })
          .catch((finalError) => {
            console.error('All save methods failed:', finalError);
            this.showManualSaveOption(simpleExpendData);
          });
      });
  }

  private resetForm() {
    this.Voucher = new ExpenseModel();
    if (this.cmbHeads && this.cmbHeads.nativeElement) {
      this.cmbHeads.nativeElement.focus();
    }
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
      return new Date().toISOString().split('T')[0];
    }
  }

  private showManualSaveOption(expendData: any) {
    const sql = `INSERT INTO expend (Date, HeadID, Desc, Amount, IsPosted) VALUES ('${expendData.Date}', ${expendData.HeadID}, '${expendData.Desc}', ${expendData.Amount}, 0);`;
    
    console.log('MANUAL DATABASE SAVE REQUIRED:');
    console.log('===============================');
    console.log('SQL Statement:', sql);
    console.log('===============================');
    
    this.alert.Error(
      `Automatic save failed. Manual database entry required:
      
📋 SQL STATEMENT TO RUN:
${sql}

💾 INSTRUCTIONS:
1. Open phpMyAdmin or your database management tool
2. Select the "db_cement" database
3. Go to SQL tab and paste the above statement
4. Click "Go" to execute

🔧 PERMANENT FIX NEEDED:
The expend API endpoint needs to be fixed by your IT team.`,
      'Database Manual Entry Required'
    );
  }

  Round(amnt: number) {
    return Math.round(amnt);
  }

  getSelectedAccountName(): string {
    const selected = this.ExpenseHeads.find(head => head.HeadID == Number(this.Voucher.HeadID));
    return selected ? selected.Head : '';
  }

  getSelectedAccountType(): string {
    const selected = this.ExpenseHeads.find(head => head.HeadID == Number(this.Voucher.HeadID));
    return selected ? selected.AcctType || 'N/A' : '';
  }

  getSelectedAccountBalance(): number {
    const selected = this.ExpenseHeads.find(head => head.HeadID == Number(this.Voucher.HeadID));
    return selected ? selected.Balance || 0 : 0;
  }

  showAddCategoryModal() {
    this.showAddModal = true;
    this.newCategory = { name: '', type: 'Expense' };
  }

  hideAddCategoryModal() {
    this.showAddModal = false;
    this.newCategory = { name: '', type: 'Expense' };
  }

  addNewCategory() {
    if (!this.newCategory.name || this.newCategory.name.trim().length < 2) {
      this.alert.Error('Please enter a category name (at least 2 characters)', 'Validation Error');
      return;
    }

    // Generate new HeadID (use timestamp to avoid conflicts)
    const newHeadID = Date.now();
    
    // Add new category to the list
    const newExpenseHead = {
      HeadID: newHeadID,
      Head: this.newCategory.name.trim(),
      AcctType: this.newCategory.type,
      Balance: 0
    };

    this.ExpenseHeads.push(newExpenseHead);
    
    // Sort the list alphabetically
    this.ExpenseHeads.sort((a, b) => a.Head.localeCompare(b.Head));

    // Select the newly added category
    this.Voucher.HeadID = newHeadID.toString();

    // Hide modal and show success message
    this.hideAddCategoryModal();
    this.alert.Sucess(`Category "${newExpenseHead.Head}" added successfully!`, 'Success', 1);

    console.log('New expense category added:', newExpenseHead);
  }

  toggleExpensesList() {
    this.showExpensesList = !this.showExpensesList;
    if (this.showExpensesList && this.expensesList.length === 0) {
      this.loadExpensesList();
    }
  }

  loadExpensesList() {
    this.loadingExpenses = true;
    
    // Try to load from qryexpense endpoint first
    this.http.getData('qryexpense?filter=1=1&orderby=Date DESC')
      .then((expenses: any) => {
        console.log('Expenses loaded from qryexpense:', expenses);
        this.expensesList = Array.isArray(expenses) ? expenses : [];
        this.loadingExpenses = false;
      })
      .catch((error) => {
        console.log('qryexpense failed, trying vouchers endpoint:', error);
        
        // Fallback: Load from vouchers where RefType = 4 (expenses)
        this.http.getData('qryvouchers?filter=RefType=4&orderby=Date DESC')
          .then((vouchers: any) => {
            console.log('Expenses loaded from vouchers:', vouchers);
            this.expensesList = Array.isArray(vouchers) ? vouchers : [];
            this.loadingExpenses = false;
          })
          .catch((voucherError) => {
            console.error('Both expense loading methods failed:', voucherError);
            this.expensesList = [];
            this.loadingExpenses = false;
            this.alert.Error('Unable to load expenses list', 'Error');
          });
      });
  }

  getExpenseHeadName(headId: any): string {
    console.log('Getting expense head name for ID:', headId);
    
    // First try to find in ExpenseHeads array
    const head = this.ExpenseHeads.find(h => h.HeadID == headId);
    if (head) {
      return head.Head;
    }
    
    // If not found, try common expense categories based on ID patterns
    const commonExpenses: { [key: string]: string } = {
      '1771466710831': '🚚 Express Transport',
      '1': '⛽ Fuel & Vehicle Expenses', 
      '2': '📋 Office Supplies & Stationery',
      '3': '⚡ Monthly Electricity Bill',
      '999001': '🏢 Office Expenses',
      '999002': '🚗 Travel & Transportation', 
      '999003': '⚡ Utilities & Bills',
      '999004': '🏠 Rent & Maintenance',
      '999005': '📱 Communication',
      '999006': '💼 Miscellaneous Expenses'
    };
    
    // Check if we have a predefined category for this ID
    if (commonExpenses[String(headId)]) {
      return commonExpenses[String(headId)];
    }
    
    // For unknown IDs, provide a descriptive fallback
    if (headId) {
      return `💰 Expense Category #${headId}`;
    }
    
    return '❓ Unknown Category';
  }

  formatDate(date: string): string {
    try {
      const d = new Date(date);
      return d.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      });
    } catch {
      return date;
    }
  }

  getTotalExpenses(): number {
    return this.expensesList.reduce((total, expense) => {
      const amount = Number(expense.Amount || expense.Debit || 0);
      return total + amount;
    }, 0);
  }

  formatCurrency(amount: any): string {
    const numAmount = Number(amount || 0);
    return numAmount.toLocaleString();
  }
}
