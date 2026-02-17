import { Component, OnInit } from '@angular/core';
import { BsModalRef, BsModalService } from 'ngx-bootstrap/modal';
import { NgxSpinnerService } from 'ngx-spinner';
import { Accounts } from '../../../models/accounts.model';
import { AccountsService } from '../../../services/accounts.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-dead-accounts',
  templateUrl: './dead-accounts.component.html',
  styleUrls: ['./dead-accounts.component.scss']
})
export class DeadAccountsComponent implements OnInit {
  deadAccounts: Accounts[] = [];
  filteredAccounts: Accounts[] = [];
  searchTerm: string = '';
  loading: boolean = false;
  currentPage: number = 1;
  itemsPerPage: number = 10;
  totalItems: number = 0;

  // Columns to display
  displayedColumns: string[] = [
    'AccountCode', 'AccountName', 'ContactPerson', 'City', 
    'PhoneNo', 'Balance', 'Status', 'Actions'
  ];

  bsModalRef?: BsModalRef;

  constructor(
    private accountsService: AccountsService,
    private spinner: NgxSpinnerService,
    private modalService: BsModalService
  ) { }

  ngOnInit(): void {
    this.loadDeadAccounts();
  }

  // Load dead accounts from backend
  async loadDeadAccounts(): Promise<void> {
    this.loading = true;
    this.spinner.show();
    
    try {
      this.deadAccounts = await this.accountsService.getDeadAccounts();
      this.filteredAccounts = [...this.deadAccounts];
      this.totalItems = this.deadAccounts.length;
    } catch (error) {
      console.error('Error loading dead accounts:', error);
      Swal.fire('Error', 'Failed to load dead accounts', 'error');
    } finally {
      this.loading = false;
      this.spinner.hide();
    }
  }

  // Search functionality
  onSearch(): void {
    if (!this.searchTerm.trim()) {
      this.filteredAccounts = [...this.deadAccounts];
    } else {
      this.filteredAccounts = this.deadAccounts.filter(account => 
        account.AccountName.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        account.AccountCode?.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        account.ContactPerson.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        account.City.toLowerCase().includes(this.searchTerm.toLowerCase())
      );
    }
    this.totalItems = this.filteredAccounts.length;
    this.currentPage = 1; // Reset to first page
  }

  // Clear search
  clearSearch(): void {
    this.searchTerm = '';
    this.onSearch();
  }

  // Reactivate account
  async reactivateAccount(account: Accounts): Promise<void> {
    const result = await Swal.fire({
      title: 'Reactivate Account?',
      text: `Are you sure you want to reactivate "${account.AccountName}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Reactivate',
      cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
      this.spinner.show();
      try {
        await this.accountsService.reactivateAccount(account.AccountID);
        Swal.fire('Success', 'Account has been reactivated successfully', 'success');
        await this.loadDeadAccounts(); // Refresh the list
      } catch (error) {
        console.error('Error reactivating account:', error);
        Swal.fire('Error', 'Failed to reactivate account', 'error');
      } finally {
        this.spinner.hide();
      }
    }
  }

  // Delete account permanently
  async deleteAccount(account: Accounts): Promise<void> {
    const result = await Swal.fire({
      title: 'Delete Account Permanently?',
      text: `Are you sure you want to permanently delete "${account.AccountName}"? This action cannot be undone!`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Delete',
      cancelButtonText: 'Cancel',
      input: 'text',
      inputPlaceholder: 'Type "DELETE" to confirm',
      inputValidator: (value) => {
        if (value !== 'DELETE') {
          return 'Please type "DELETE" to confirm';
        }
        return null;
      }
    });

    if (result.isConfirmed) {
      this.spinner.show();
      try {
        await this.accountsService.deleteAccount(account.AccountID);
        Swal.fire('Deleted', 'Account has been deleted permanently', 'success');
        await this.loadDeadAccounts(); // Refresh the list
      } catch (error) {
        console.error('Error deleting account:', error);
        Swal.fire('Error', 'Failed to delete account', 'error');
      } finally {
        this.spinner.hide();
      }
    }
  }

  // View account details
  viewAccountDetails(account: Accounts): void {
    Swal.fire({
      title: 'Account Details',
      html: `
        <div class="text-left">
          <p><strong>Account Code:</strong> ${account.AccountCode || 'N/A'}</p>
          <p><strong>Account Name:</strong> ${account.AccountName}</p>
          <p><strong>Contact Person:</strong> ${account.ContactPerson}</p>
          <p><strong>Address:</strong> ${account.Address}</p>
          <p><strong>City:</strong> ${account.City}</p>
          <p><strong>Phone:</strong> ${account.PhoneNo}</p>
          <p><strong>Mobile:</strong> ${account.MobileNo}</p>
          <p><strong>Email:</strong> ${account.Email}</p>
          <p><strong>Balance:</strong> ${account.Balance}</p>
          <p><strong>Credit Limit:</strong> ${account.CreditLimit}</p>
          <p><strong>Status:</strong> ${account.Status}</p>
          <p><strong>Remarks:</strong> ${account.Remarks}</p>
        </div>
      `,
      showConfirmButton: false,
      showCloseButton: true,
      width: '600px'
    });
  }

  // Pagination
  get paginatedAccounts(): Accounts[] {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    return this.filteredAccounts.slice(startIndex, startIndex + this.itemsPerPage);
  }

  get totalPages(): number {
    return Math.ceil(this.totalItems / this.itemsPerPage);
  }

  goToPage(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
    }
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  // Refresh data
  async refresh(): Promise<void> {
    await this.loadDeadAccounts();
    Swal.fire({
      icon: 'success',
      title: 'Refreshed',
      text: 'Data has been refreshed',
      timer: 1500,
      showConfirmButton: false
    });
  }

  // Getter methods for template bindings
  getDeadAccountsCount(): number {
    return this.deadAccounts.filter(a => a.Status === 'Dead').length;
  }

  getInactiveAccountsCount(): number {
    return this.deadAccounts.filter(a => a.Status === 'Inactive').length;
  }

  getClosedAccountsCount(): number {
    return this.deadAccounts.filter(a => a.Status === 'Closed').length;
  }

  getTotalBalance(): number {
    return this.deadAccounts.reduce((sum, a) => sum + a.Balance, 0);
  }
}