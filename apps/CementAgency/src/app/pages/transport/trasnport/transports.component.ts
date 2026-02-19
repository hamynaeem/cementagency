import { Component, OnInit } from '@angular/core';
import { TransportService, Transport } from '../../../services/transport.service';
import { MyToastService } from '../../../services/toaster.server';
import { HttpBase } from '../../../services/httpbase.service';

@Component({
  selector: 'app-transports',
  templateUrl: './transports.component.html',
  styleUrls: ['./transports.component.scss']
})
export class TransportsComponent implements OnInit {
  // SQL query to create transports table:
  // CREATE TABLE transports (
  //   TransportID INT PRIMARY KEY AUTO_INCREMENT,
  //   TransportName VARCHAR(255) NOT NULL,
  //   VehicleNo VARCHAR(255) NOT NULL,
  //   DriverName VARCHAR(255)
  // );

  public transports: Transport[] = [];
  public loading = false;
  public searchTerm = '';
  public selectedTransport: Transport | null = null;
  public showQuickAdd = false;
  public showFtCrud = true; // Toggle for showing original ft-crud component
  
  public form = {
    title: 'Transports',
    tableName: 'transports',
    pk: 'TransportID',
    columns: [
      {
        fldName: 'TransportName',
        control: 'input',
        type: 'text',
        label: 'Transport Name',
        required: true,
        size: 12
      },
      {
        fldName: 'VehicleNo',
        control: 'input',
        type: 'text',
        label: 'Vehicle No',
        required: true,
        size: 12
      },
      {
        fldName: 'DriverName',
        control: 'input',
        type: 'text',
        label: 'Driver Name',
        size: 12
      }
    ]
  };
  
  public list = {
    tableName: 'transports',
    pk: 'TransportID',
    columns: this.form.columns
  };

  public newTransport: Transport = {
    TransportName: '',
    VehicleNo: '',
    DriverName: ''
  };

  constructor(
    private transportService: TransportService,
    private toaster: MyToastService,
    private httpBase: HttpBase
  ) { }

  async ngOnInit() {
    await this.loadTransports();
  }

  /**
   * Load all transports from the API
   */
  async loadTransports() {
    try {
      this.loading = true;
      this.transports = await this.transportService.getTransports();
    } catch (error) {
      console.error('Error loading transports:', error);
      this.toaster.Error('Error loading transports', 'Error');
    } finally {
      this.loading = false;
    }
  }

  /**
   * Create a new transport
   */
  async createTransport(transportData?: Transport) {
    try {
      const dataToSubmit = transportData || this.newTransport;
      
      if (!this.validateTransport(dataToSubmit)) {
        return;
      }

      this.loading = true;
      const result = await this.transportService.createTransport(dataToSubmit);
      
      if (result) {
        this.toaster.Sucess('Transport created successfully', 'Success');
        this.resetForm();
        await this.loadTransports();
      }
    } catch (error) {
      console.error('Error creating transport:', error);
      const errorMessage = error instanceof Error ? error.message : 'Error creating transport';
      this.toaster.Error(errorMessage, 'Error');
    } finally {
      this.loading = false;
    }
  }

  /**
   * Update an existing transport
   */
  async updateTransport(transport: Transport) {
    try {
      if (!this.validateTransport(transport)) {
        return;
      }

      this.loading = true;
      const result = await this.transportService.updateTransport(transport);
      
      if (result) {
        this.toaster.Sucess('Transport updated successfully', 'Success');
        await this.loadTransports();
      }
    } catch (error) {
      console.error('Error updating transport:', error);
      const errorMessage = error instanceof Error ? error.message : 'Error updating transport';
      this.toaster.Error(errorMessage, 'Error');
    } finally {
      this.loading = false;
    }
  }

  /**
   * Delete a transport
   */
  async deleteTransport(transport: Transport) {
    try {
      if (!transport.TransportID) {
        this.toaster.Error('Invalid transport ID', 'Error');
        return;
      }

      if (confirm(`Are you sure you want to delete ${transport.TransportName}?`)) {
        this.loading = true;
        const result = await this.transportService.deleteTransport(transport.TransportID);
        
        if (result) {
          this.toaster.Sucess('Transport deleted successfully', 'Success');
          await this.loadTransports();
        }
      }
    } catch (error) {
      console.error('Error deleting transport:', error);
      const errorMessage = error instanceof Error ? error.message : 'Error deleting transport';
      this.toaster.Error(errorMessage, 'Error');
    } finally {
      this.loading = false;
    }
  }

  /**
   * Search transports
   */
  async searchTransports() {
    try {
      if (!this.searchTerm.trim()) {
        await this.loadTransports();
        return;
      }

      this.loading = true;
      this.transports = await this.transportService.searchTransports(this.searchTerm);
    } catch (error) {
      console.error('Error searching transports:', error);
      this.toaster.Error('Error searching transports', 'Error');
    } finally {
      this.loading = false;
    }
  }

  /**
   * Edit a transport
   */
  editTransport(transport: Transport) {
    this.selectedTransport = { ...transport };
  }

  /**
   * Save edited transport
   */
  async saveTransport() {
    if (this.selectedTransport) {
      await this.updateTransport(this.selectedTransport);
      this.selectedTransport = null;
    }
  }

  /**
   * Cancel editing
   */
  cancelEdit() {
    this.selectedTransport = null;
  }

  /**
   * Open create form using the existing modal system
   */
  async openCreateForm() {
    try {
      const result = await this.httpBase.openForm(this.form, this.newTransport);
      if (result === 'save') {
        await this.loadTransports();
      }
    } catch (error) {
      console.error('Error opening form:', error);
    }
  }

  /**
   * Open edit form using the existing modal system
   */
  async openEditForm(transport: Transport) {
    try {
      const result = await this.httpBase.openForm(this.form, transport);
      if (result === 'save') {
        await this.loadTransports();
      }
    } catch (error) {
      console.error('Error opening form:', error);
    }
  }

  /**
   * Validate transport data
   */
  private validateTransport(transport: Transport): boolean {
    if (!transport.TransportName?.trim()) {
      this.toaster.Error('Transport Name is required', 'Validation Error');
      return false;
    }

    if (!transport.VehicleNo?.trim()) {
      this.toaster.Error('Vehicle No is required', 'Validation Error');
      return false;
    }

    return true;
  }

  /**
   * Reset the form
   */
  private resetForm() {
    this.newTransport = {
      TransportName: '',
      VehicleNo: '',
      DriverName: ''
    };
  }

  /**
   * Toggle quick add form visibility
   */
  toggleQuickAdd() {
    this.showQuickAdd = !this.showQuickAdd;
    if (this.showQuickAdd) {
      this.resetForm();
    }
  }

  /**
   * Toggle ft-crud component visibility
   */
  toggleFtCrud() {
    this.showFtCrud = !this.showFtCrud;
  }

  /**
   * Get transport statistics
   */
  getTransportStats() {
    return {
      total: this.transports.length,
      withDriver: this.transports.filter(t => t.DriverName?.trim()).length,
      withoutDriver: this.transports.filter(t => !t.DriverName?.trim()).length
    };
  }

}
