import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../environments/environment';

export interface Transport {
  TransportID?: number;
  TransportName: string;
  VehicleNo: string;
  DriverName?: string;
  created_at?: string;
  updated_at?: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class TransportService {

  private apiUrl = `${environment.INSTANCE_URL}transports_api.php`;

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    return new HttpHeaders({
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    });
  }

  /**
   * Get all transports with optional filtering
   */
  async getTransports(filter: string = '', orderBy: string = 'TransportName'): Promise<Transport[]> {
    try {
      const params: any = {};
      if (filter) {
        params.filter = filter;
      }
      if (orderBy) {
        params.orderby = orderBy;
      }
      
      const response = await this.http.get<ApiResponse<Transport[]>>(
        this.apiUrl, 
        { 
          headers: this.getHeaders(),
          params: params
        }
      ).toPromise();

      if (response?.success && response.data) {
        return response.data;
      } else {
        throw new Error(response?.error || 'Failed to fetch transports');
      }
    } catch (error) {
      console.error('Error fetching transports:', error);
      throw error;
    }
  }

  /**
   * Get a specific transport by ID
   */
  async getTransportById(id: number): Promise<Transport> {
    try {
      const response = await this.http.get<ApiResponse<Transport>>(
        this.apiUrl,
        {
          headers: this.getHeaders(),
          params: { id: id.toString() }
        }
      ).toPromise();

      if (response?.success && response.data) {
        return response.data;
      } else {
        throw new Error(response?.error || 'Transport not found');
      }
    } catch (error) {
      console.error('Error fetching transport:', error);
      throw error;
    }
  }

  /**
   * Create a new transport
   */
  async createTransport(transport: Transport): Promise<Transport> {
    try {
      const response = await this.http.post<ApiResponse<Transport>>(
        this.apiUrl,
        transport,
        { headers: this.getHeaders() }
      ).toPromise();

      if (response?.success && response.data) {
        return response.data;
      } else {
        throw new Error(response?.error || 'Failed to create transport');
      }
    } catch (error) {
      console.error('Error creating transport:', error);
      throw error;
    }
  }

  /**
   * Update an existing transport
   */
  async updateTransport(transport: Transport): Promise<Transport> {
    try {
      if (!transport.TransportID) {
        throw new Error('Transport ID is required for update');
      }

      const response = await this.http.put<ApiResponse<Transport>>(
        this.apiUrl,
        transport,
        { headers: this.getHeaders() }
      ).toPromise();

      if (response?.success && response.data) {
        return response.data;
      } else {
        throw new Error(response?.error || 'Failed to update transport');
      }
    } catch (error) {
      console.error('Error updating transport:', error);
      throw error;
    }
  }

  /**
   * Delete a transport
   */
  async deleteTransport(id: number): Promise<boolean> {
    try {
      const response = await this.http.delete<ApiResponse<Transport>>(
        this.apiUrl,
        {
          headers: this.getHeaders(),
          params: { id: id.toString() }
        }
      ).toPromise();

      if (response?.success) {
        return true;
      } else {
        throw new Error(response?.error || 'Failed to delete transport');
      }
    } catch (error) {
      console.error('Error deleting transport:', error);
      throw error;
    }
  }

  /**
   * Search transports by name or vehicle number
   */
  async searchTransports(searchTerm: string): Promise<Transport[]> {
    try {
      const filter = `TransportName LIKE '%${searchTerm}%' OR VehicleNo LIKE '%${searchTerm}%'`;
      return await this.getTransports(filter);
    } catch (error) {
      console.error('Error searching transports:', error);
      throw error;
    }
  }

  /**
   * Get transports for autocomplete/dropdown
   */
  async getTransportsForDropdown(): Promise<{value: number, text: string}[]> {
    try {
      const transports = await this.getTransports('', 'TransportName');
      return transports.map(transport => ({
        value: transport.TransportID!,
        text: `${transport.TransportName} (${transport.VehicleNo})`
      }));
    } catch (error) {
      console.error('Error fetching transports for dropdown:', error);
      throw error;
    }
  }
}