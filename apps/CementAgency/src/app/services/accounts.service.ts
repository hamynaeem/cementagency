import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BsModalService } from 'ngx-bootstrap/modal';
import { NgxSpinnerService } from 'ngx-spinner';
import { Accounts } from '../models/accounts.model';
import { HttpBase } from './httpbase.service';
import { AuthenticationService } from './authentication.service';

@Injectable({
  providedIn: 'root'
})
export class AccountsService extends HttpBase {

  constructor(
    http: HttpClient,
    spinner: NgxSpinnerService,
    modalService: BsModalService,
    auth: AuthenticationService
  ) {
    super(http, spinner, modalService, auth);
  }

  // Get all accounts
  async getAllAccounts(): Promise<Accounts[]> {
    try {
      const response = await this.getData('accounts');
      return response as Accounts[];
    } catch (error) {
      console.error('Error fetching accounts:', error);
      throw error;
    }
  }

  // Get dead accounts (Status = 'Dead' or 'Inactive')
  async getDeadAccounts(): Promise<Accounts[]> {
    try {
      const response = await this.getData('accounts', 'Status IN ("Dead","Inactive","Closed")');
      return response as Accounts[];
    } catch (error) {
      console.error('Error fetching dead accounts:', error);
      throw error;
    }
  }

  // Get active accounts
  async getActiveAccounts(): Promise<Accounts[]> {
    try {
      const response = await this.getData('accounts', 'Status="Active"');
      return response as Accounts[];
    } catch (error) {
      console.error('Error fetching active accounts:', error);
      throw error;
    }
  }

  // Mark account as dead
  async markAccountAsDead(accountId: string): Promise<any> {
    try {
      const updateData = {
        AccountID: accountId,
        Status: 'Dead'
      };
      const response = await this.postData('accounts', updateData);
      return response;
    } catch (error) {
      console.error('Error marking account as dead:', error);
      throw error;
    }
  }

  // Reactivate account
  async reactivateAccount(accountId: string): Promise<any> {
    try {
      const updateData = {
        AccountID: accountId,
        Status: 'Active'
      };
      const response = await this.postData('accounts', updateData);
      return response;
    } catch (error) {
      console.error('Error reactivating account:', error);
      throw error;
    }
  }

  // Delete account permanently
  async deleteAccount(accountId: string): Promise<any> {
    try {
      const response = await this.Delete('accounts', accountId);
      return response;
    } catch (error) {
      console.error('Error deleting account:', error);
      throw error;
    }
  }

  // Search accounts by name or code
  async searchAccounts(searchTerm: string): Promise<Accounts[]> {
    try {
      const filter = `AccountName LIKE "%${searchTerm}%" OR AccountCode LIKE "%${searchTerm}%"`;
      const response = await this.getData('accounts', filter);
      return response as Accounts[];
    } catch (error) {
      console.error('Error searching accounts:', error);
      throw error;
    }
  }

  // Get account by ID
  async getAccountById(accountId: string): Promise<Accounts> {
    try {
      const filter = `AccountID="${accountId}"`;
      const response = await this.getData('accounts', filter) as Accounts[];
      return response[0];
    } catch (error) {
      console.error('Error fetching account by ID:', error);
      throw error;
    }
  }
}