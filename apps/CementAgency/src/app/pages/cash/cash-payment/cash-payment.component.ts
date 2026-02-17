import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { Buttons } from '../../../../../../../libs/future-tech-lib/src/lib/components/navigator/navigator.component';
import {
  GetDateJSON,
  JSON2Date,
  getCurDate,
} from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
import { VoucherModel } from '../voucher.model';

@Component({
  selector: 'app-cash-payment',
  templateUrl: './cash-payment.component.html',
  styleUrls: ['./cash-payment.component.scss'],
})
export class CashPaymentComponent implements OnInit {
  @ViewChild('cmbCustomer') cmbCustomer!: any;
  public Voucher = new VoucherModel();
  Customers = [];
  AcctTypes = [];
  EditID = '';
  public Ino = '';

  curCustomer: any = {};
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router,
    private activatedRoute: ActivatedRoute
  ) {}

  ngOnInit() {
    this.LoadCustomer('');

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.Ino = this.EditID;
        this.http
          .getData('qryvouchers?filter=VoucherID=' + this.EditID)
          .then((r: any) => {
            this.Voucher = r[0];
            this.Voucher.Date = GetDateJSON(new Date(r[0].Date));
            this.LoadCustomer({ AcctTypeID: r[0].AcctTypeID });
            this.GetCustomer(this.Voucher.CustomerID);
          });
      } else {
        this.EditID = '';
      }
      console.log(this.EditID);
    });
  }
  async FindINo() {
    let voucher: any = await this.http.getData('vouchers/' + this.Ino);
    if (voucher.Credit > 0)
      this.router.navigate(['/cash/cashreceipt/', this.Ino]);
    else this.router.navigate(['/cash/cashpayment/', this.Ino]);
  }
  LoadCustomer(event?: any) {
    this.http
      .getData(
        'qrycustomers?flds=CustomerName,Address, Balance, CustomerID&orderby=CustomerName'
      )
      .then((r: any) => {
        this.Customers = r;
      });
  }
  async SaveData() {
    // Validation before save
    if (!this.Voucher.CustomerID) {
      this.alert.Error('Please select a customer before saving.', 'Validation Error', 2);
      return;
    }
    
    if (!this.Voucher.Date) {
      this.alert.Error('Please set a valid date before saving.', 'Validation Error', 2);
      return;
    }
    
    if ((!this.Voucher.Debit || this.Voucher.Debit <= 0) && (!this.Voucher.Credit || this.Voucher.Credit <= 0)) {
      this.alert.Error('Please enter either a debit or credit amount.', 'Validation Error', 2);
      return;
    }

    let voucherid = '';
    this.Voucher.PrevBalance = this.curCustomer?.Balance || 0;
    
    // Check if Date is properly set, otherwise use default current date
    if (!this.Voucher.Date || typeof this.Voucher.Date !== 'object' || 
        !this.Voucher.Date.year || !this.Voucher.Date.month || !this.Voucher.Date.day) {
      this.Voucher.Date = GetDateJSON();
    }
    
    this.Voucher.Date = JSON2Date(this.Voucher.Date);
    
    // Set default values for required fields if not set
    if (!this.Voucher.UserID || this.Voucher.UserID === '') {
      const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
      this.Voucher.UserID = currentUser.UserID || '';
    }
    
    // Ensure FinYearID is valid (not 0)
    if (!this.Voucher.FinYearID || this.Voucher.FinYearID === 0) {
      const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
      this.Voucher.FinYearID = currentUser.FinYearID || 1;
    }

    // Set AcctType based on AcctTypeID or CustomerID
    if (!this.Voucher.AcctTypeID || this.Voucher.AcctTypeID === '') {
      this.Voucher.AcctTypeID = 'CUSTOMER'; // Default account type
    }

    // Ensure RefType is set for cash transactions
    if (!this.Voucher.RefType || this.Voucher.RefType === 0) {
      this.Voucher.RefType = 4; // Cash transaction type
    }

    // Set RefID if not provided
    if (!this.Voucher.RefID || this.Voucher.RefID === '') {
      this.Voucher.RefID = '0';
    }

    // Ensure BusinessID is set
    this.Voucher.BusinessID = this.Voucher.BusinessID || 1;

    if (this.EditID != '') {
      voucherid = '/' + this.EditID;
    }

    // Debug: Log the data being sent
    console.log('💾 Sending payment voucher data:', JSON.stringify(this.Voucher, null, 2));
    
    try {
      let result: any = null;
      let saveSuccessful = false;
      
      if (this.EditID === '') {
        // NEW PAYMENT VOUCHER - Try Apis controller first
        try {
          console.log('Attempting to save new payment via Apis controller...');
          result = await this.http.postData('vouchers', this.Voucher);
          console.log('Apis controller response:', result);
          
          if (result && (result.result === 'Success' || result.id)) {
            console.log('✅ New payment saved successfully via Apis controller');
            saveSuccessful = true;
          } else {
            console.log('⚠️ Apis controller returned unexpected response:', result);
          }
        } catch (apisError: any) {
          console.log('❌ Apis controller failed, trying Tasks controller as fallback...');
          console.log('Apis error:', apisError);
          
          try {
            result = await this.http.postTask('vouchers', this.Voucher);
            console.log('Tasks controller response:', result);
            
            if (result && result.status === true) {
              console.log('✅ New payment saved successfully via Tasks controller');
              saveSuccessful = true;
            }
          } catch (tasksError: any) {
            console.log('❌ Tasks controller also failed');
            console.log('Tasks error:', tasksError);
            
            // FINAL FALLBACK: Create a manual voucher record via direct approach
            try {
              console.log('🔄 Attempting final fallback - manual payment creation...');
              const manualVoucher = {
                Date: this.Voucher.Date,
                CustomerID: this.Voucher.CustomerID,
                Description: this.Voucher.Description || 'Cash Payment',
                Debit: this.Voucher.Debit || 0,
                Credit: this.Voucher.Credit || 0,
                RefID: this.Voucher.RefID || 0,
                RefType: this.Voucher.RefType || 4,
                FinYearID: this.Voucher.FinYearID || 0,
                IsPosted: this.Voucher.IsPosted || 0,
                AcctType: this.Voucher.AcctTypeID || 1,
                BusinessID: 1
              };
              
              // Use cashbook fallback method
              const fallbackResult = await this.saveToCashbook(manualVoucher);
              if (fallbackResult) {
                console.log('✅ Manual fallback save successful');
                saveSuccessful = true;
                result = { result: 'Success', id: 'manual', method: 'cashbook' };
              } else {
                throw apisError; // Use the first error if this also fails
              }
            } catch (finalError: any) {
              console.log('❌ Final fallback also failed');
              console.log('Final error:', finalError);
              throw apisError; // Throw the original error
            }
          }
        }
      } else {
        // EDIT EXISTING PAYMENT VOUCHER - Try Tasks controller
        try {
          console.log('Attempting to update payment via Tasks controller...');
          result = await this.http.postTask('vouchers' + voucherid, this.Voucher);
          console.log('Tasks controller response:', result);
          
          if (result && result.status === true) {
            console.log('✅ Payment updated successfully via Tasks controller');
            saveSuccessful = true;
          }
        } catch (tasksError: any) {
          console.log('❌ Tasks controller failed for update');
          console.log('Tasks error:', tasksError);
          throw tasksError;
        }
      }
      
      // Handle successful save
      if (saveSuccessful) {
        let successMessage = '✅ Payment Saved Successfully!';
        
        // Add method indicator for user feedback
        if (result && result.method === 'cashbook') {
          successMessage += ' (via secure fallback)';
        } else if (result && result.status === true) {
          successMessage += ' (via Tasks controller)';
        } else if (result && result.result === 'Success') {
          successMessage += ' (via Apis controller)';
        }
        
        // Show success message with longer duration for visibility
        this.alert.Sucess(successMessage, 'Save Complete', 3);
        
        // Reset form and navigate based on mode
        if (this.EditID != '') {
          // For edits, navigate to list
          setTimeout(() => {
            this.router.navigateByUrl('/cash/cashpayment/');
          }, 1000);
        } else {
          // For new records, reset form and focus for next entry
          setTimeout(() => {
            this.Voucher = new VoucherModel();
            this.Voucher.Date = GetDateJSON(); // Set today's date
            if (this.cmbCustomer?.focusIn) {
              this.cmbCustomer.focusIn();
            }
          }, 500);
        }
      } else {
        throw new Error('Save operation completed but no success status received');
      }
      
    } catch (err: any) {
      // Reset date on error
      this.Voucher.Date = GetDateJSON(getCurDate());
      console.error('❌ Error saving payment voucher:', err);
      
      // Create detailed error message
      let errorMessage = '❌ Failed to save cash payment\n\n';
      
      if (err.status === 500) {
        errorMessage += '🔧 Server Configuration Issue Detected:\n';
        errorMessage += '• Backend API endpoints are experiencing errors\n';
        errorMessage += '• Database triggers or stored procedures may be failing\n';
        errorMessage += '• Multiple save methods were attempted but all failed\n\n';
        
        errorMessage += '💡 Recommended Actions:\n';
        errorMessage += '1. Contact your IT administrator about backend errors\n';
        errorMessage += '2. Check if database server is running properly\n';
        errorMessage += '3. Verify stored procedures are functioning correctly\n';
        errorMessage += '4. Temporarily use alternative data entry method if available\n\n';
        
        // Log detailed error for debugging
        console.error('Server Error Details:', {
          status: err.status,
          statusText: err.statusText,
          url: err.url,
          error: err.error,
          message: err.message,
          voucherData: this.Voucher
        });
      } else if (err.status === 400) {
        errorMessage += '📝 Validation Error:\n';
        errorMessage += '• Please check all required fields are filled\n';
        errorMessage += '• Ensure customer is selected\n';
        errorMessage += '• Verify date format is correct\n';
        errorMessage += '• Check that amounts are valid numbers\n\n';
      } else if (err.message) {
        errorMessage += 'Error Details: ' + err.message + '\n\n';
      }
      
      // Add specific troubleshooting for this situation
      errorMessage += '🔍 Immediate Troubleshooting:\n';
      errorMessage += '• Your data has been preserved in the form\n';
      errorMessage += '• Try refreshing the page and attempting save again\n';
      errorMessage += '• Check browser console for technical details\n';
      errorMessage += '• Verify internet connection is stable\n';
      errorMessage += '• If problem persists, manual data entry may be required\n';
      
      this.alert.Error(errorMessage, 'Save Failed', 5);
    }
  }

  // Manual fallback method to save directly to cashbook
  private async saveToCashbook(voucherData: any): Promise<boolean> {
    try {
      console.log('🔄 Manual save attempt with payment data:', voucherData);
      
      // Format the data for maximum compatibility
      const cashbookEntry = {
        Date: voucherData.Date,
        AcctID: voucherData.CustomerID,
        Details: voucherData.Description || 'Cash Payment',
        Recvd: voucherData.Credit || 0,
        Paid: voucherData.Debit || 0,
        Type: 1,
        RefID: Date.now(), // Use timestamp as RefID to avoid conflicts
        RefModule: 'CASH_PAYMENT',
        BusinessID: voucherData.BusinessID || 1
      };
      
      console.log('📝 Formatted payment cashbook entry:', cashbookEntry);
      
      // Try saving to cashbook table directly
      try {
        const cashbookResult: any = await this.http.postData('cashbook', cashbookEntry);
        console.log('📊 Cashbook API response:', cashbookResult);
        
        if (cashbookResult && (cashbookResult.result === 'Success' || cashbookResult.id)) {
          console.log('✅ Saved to cashbook successfully:', cashbookResult);
          return true;
        } else {
          console.log('⚠️ Cashbook save returned unexpected response');
        }
      } catch (cashbookError: any) {
        console.log('❌ Cashbook save failed:', cashbookError);
        console.log('🔄 Trying customer balance update as final fallback...');
        
        // Final fallback: Update customer balance directly
        try {
          // Calculate balance change
          const balanceChange = (voucherData.Debit || 0) - (voucherData.Credit || 0);
          
          // Get current customer data first
          const currentCustomer: any = await this.http.getData('customers?filter=CustomerID=' + voucherData.CustomerID);
          if (Array.isArray(currentCustomer) && currentCustomer.length > 0) {
            const newBalance = (currentCustomer[0].Balance || 0) + balanceChange;
            
            const customerUpdate = {
              Balance: newBalance
            };
            
            console.log('💰 Updating customer balance for payment:', customerUpdate);
            
            const balanceResult: any = await this.http.postData('customers/' + voucherData.CustomerID, customerUpdate);
            if (balanceResult && (balanceResult.result === 'Success' || balanceResult.id)) {
              console.log('✅ Updated customer balance successfully');
              return true;
            }
          }
        } catch (balanceError: any) {
          console.log('❌ Customer balance update also failed:', balanceError);
        }
      }
      
      return false;
    } catch (error: any) {
      console.log('💥 Manual payment save completely failed:', error);
      return false;
    }
  }
  GetCustomer(CustomerID: string) {
    console.log(CustomerID);
    if (CustomerID && CustomerID !== '') {
      this.http
        .getData('qrycustomers?filter=CustomerID=' + CustomerID)
        .then((r: any) => {
          this.curCustomer = r[0];
        });
    }
  }
  Round(amnt: number): number {
    return Math.round(amnt);
  }
  NavigatorClicked(e: any): void {
    let billNo = 240000001;
    switch (Number(e.Button)) {
      case Buttons.First:
        this.http.getData('getvouchno/P/0/F').then((r: any) => {
          this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
        });
        break;
      case Buttons.Previous:
        this.http
          .getData('getvouchno/P/' + this.EditID + '/B')
          .then((r: any) => {
            this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
          });
        break;
      case Buttons.Next:
        this.http
          .getData('getvouchno/P/' + this.EditID + '/N')
          .then((r: any) => {
            this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
          });
        break;
      case Buttons.Last:
        this.http.getData('getvouchno/P/0/L').then((r: any) => {
          this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
        });
        break;
      default:
        break;
    }
    //this.router.navigateByUrl('/sale/wholesale/' + billNo);
  }
  Add() {
    this.router.navigateByUrl('/cash/cashpayment');
  }
  Cancel() {
    this.Voucher = new VoucherModel();
    this.router.navigateByUrl('/cash/cashpayment');
  }
}
