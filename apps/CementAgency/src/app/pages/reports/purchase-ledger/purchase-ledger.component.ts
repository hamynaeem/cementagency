import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';

@Component({
  selector: 'app-purchase-ledger',
  templateUrl: './purchase-ledger.component.html',
  styleUrls: ['./purchase-ledger.component.scss'],
})
export class PurchaseLedgerComponent implements OnInit {
  @ViewChild('RptTable') RptTable: any;

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    ItemID: '',

    CustomerID: '',
  };
  setting = {
    Checkbox: false,
    Columns: [
      {
        label: 'Date',
        fldName: 'Date',
        width: '100px'
      },
      {
        label: 'Bill No',
        fldName: 'BookingID',
        width: '100px'
      },
      {
        label: 'Account Name',
        fldName: 'CustomerName',
        width: '180px'
      },
      {
        label: 'Product Name',
        fldName: 'ProductName',
        width: '200px'
      },
      {
        label: 'Qty (Tons)',
        fldName: 'Qty',
        sum: true,
        width: '100px',
        align: 'right'
      },
      {
        label: 'Price',
        fldName: 'PPrice',
        width: '100px',
        align: 'right'
      },
      {
        label: 'Amount',
        fldName: 'Amount',
        sum: true,
        width: '120px',
        align: 'right'
      }
    ],
    Actions: [],
    Data: []
  };

  nWhat = '1';
  Items: any[] = [{ ItemID: '1', ItemName: 'Test Item' }];

  public data: any[] = [];
  public Accounts: any;
  public selectedCustomer: any = {};
  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private cachedData: CachedDataService,
    private router: Router
  ) {
    this.Accounts = this.cachedData.Accounts$;
  }

  ngOnInit() {
    this.LoadItems();
    this.FilterData();
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title =
      'Purchase Ledger ' +
      (this.Filter.CustomerID
        ? ' Customer: ' + this.selectedCustomer.CustomerName
        : '');
    this.ps.PrintData.SubTitle =
      'From :' +
      JSON2Date(this.Filter.FromDate) +
      ' To: ' +
      JSON2Date(this.Filter.ToDate);

    this.router.navigateByUrl('/print/print-html');
  }
  CustomerSelected(e: any) {
    console.log(e);
    this.selectedCustomer = e;
  }
  FilterData() {
    // tslint:disable-next-line:quotemark
    let filter =
      "Date between '" +
      JSON2Date(this.Filter.FromDate) +
      "' and '" +
      JSON2Date(this.Filter.ToDate) +
      "'";

    if (this.Filter.CustomerID)
      filter += ' and CustomerID=' + this.Filter.CustomerID;

    if (this.Filter.ItemID)
      if (this.nWhat == '1') filter += ' and ProductID=' + this.Filter.ItemID;
      else filter += ' and UnitID=' + this.Filter.ItemID;

    // Request all necessary fields including Date and BookingID
    let flds =
      'Date,BookingID,InvoiceID,ProductName,Qty,PPrice,Amount,CustomerName,SupplierName';

    console.log('Filter:', filter);
    console.log('Fields:', flds);

    this.http
      .getData(
        `qrypurchasereport?orderby=Date,BookingID&flds=${flds}&filter=${filter}`
      )
      .then((r: any) => {
        console.log('Purchase ledger data loaded:', r);
        
        // Format the data to ensure Date and BookingID are displayed
        if (r && r.length > 0) {
          this.data = r.map((item: any) => ({
            ...item,
            Date: item.Date ? new Date(item.Date).toLocaleDateString() : 'N/A',
            BookingID: item.BookingID || item.InvoiceID || 'N/A',
            ProductName: item.ProductName || 'Unknown Product',
            Qty: Number(item.Qty) || 0,
            PPrice: Number(item.PPrice) || 0,
            Amount: Number(item.Amount) || 0
          }));
        } else {
          this.data = [];
        }
        
        console.log('Formatted data:', this.data);
      })
      .catch((error) => {
        console.error('Error loading purchase ledger data:', error);
        this.data = [];
      });
  }
  Clicked(e: any) {}

  ItemSelected(e: any) {}
  ItemChange(e: any) {
    this.LoadItems();
  }
  async LoadItems() {
    this.Items = [];
    if (this.nWhat == '1') {
      this.cachedData.Products$.subscribe((r: any) => {
        r.forEach((m: any) => {
          this.Items.push({
            ItemID: m.ProductID,
            ItemName: m.ProductName,
          });
        });
        this.Items = [...this.Items];
        console.log(this.Items);
      });
    } else if (this.nWhat == '2') {
      this.http.getData('units').then((r: any) => {
        r.forEach((m: any) => {
          this.Items.push({
            ItemID: m.ID,
            ItemName: m.UnitName,
          });
          this.Items = [...this.Items];
        });
      });
    }
  }
}
