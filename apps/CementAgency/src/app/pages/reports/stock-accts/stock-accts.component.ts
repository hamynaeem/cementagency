import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';

@Component({
  selector: 'app-stock-accts',
  templateUrl: './stock-accts.component.html',
  styleUrls: ['./stock-accts.component.scss'],
})
export class StockAcctsComponent implements OnInit {
  @ViewChild('cmbProduct') cmbProduct: any;
  public data: any[] = [];

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    StoreID: '',
    ItemID: '',
    What: '2',
  };
  colProducts = [
    {
      label: 'Date',
      fldName: 'Date',
    },
    {
      label: 'Invoice No',
      fldName: 'RefID',
    },
    {
      label: 'Customer Name',
      fldName: 'CustomerName',
    },
    {
      label: 'Stock In',
      fldName: 'QtyIn',
    },
    {
      label: 'Stock Out',
      fldName: 'QtyOut',
    },
    {
      label: 'Balance',
      fldName: 'Balance',
    },
  ];
  colUnits = [
    {
      label: 'Date',
      fldName: 'Date',
    },

    {
      label: 'Customer Name',
      fldName: 'CustomerName',
    },
    {
      label: 'Stock In',
      fldName: 'QtyIn',
    },
    {
      label: 'Stock Out',
      fldName: 'QtyOut',
    },
  ];
  setting: any = {
    Columns: [],
    Actions: [],
    Data: [],
  };
  lstDataRource: any = [];
  stores$: Observable<any[]>;

  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private cachedData: CachedDataService,
    private router: Router
  ) {
    this.stores$ = this.cachedData.Stores$;
  }

  ngOnInit() {
    this.Filter.FromDate.day = 1;
    this.http
      .getData(
        'qryproducts?flds=ProductID as ItemID, ProductName as ItemName&orderby=ProductName'
      )
      .then((r: any) => {
        this.lstDataRource = r;
      })
      .catch((error: any) => {
        console.error('Error loading products:', error);
        this.lstDataRource = [];
      });
    this.FilterData();
  }

  FilterData() {
    let filter =
      "Date between '" +
      JSON2Date(this.Filter.FromDate) +
      "' and '" +
      JSON2Date(this.Filter.ToDate) +
      "'";

    filter += ' and StoreID = ' + this.Filter.StoreID;

    if (!(this.Filter.ItemID === '' || this.Filter.ItemID === null)) {
      if (this.Filter.What == '1') {
        this.LoadProductsData(filter);
      } else {
        this.LoadUnitsData(filter);
      }
    }
  }
  LoadUnitsData(filter: any) {
    filter += " and UnitName = '" + this.Filter.ItemID + "'";

    this.http
      .getData(
        'qrystock?flds=ProductName,Stock,SPrice,PPrice,ProductID' +
          ' &filter=' +
          filter +
          '&orderby=ProductID'
      )
      .then((r: any) => {
        this.setting.Columns = this.colUnits;
        // Transform the data to match expected format
        this.data = r.map((item: any) => ({
          Date: new Date().toLocaleDateString(),
          CustomerName: 'Stock Item',
          QtyIn: item.Stock > 0 ? item.Stock : 0,
          QtyOut: item.Stock < 0 ? Math.abs(item.Stock) : 0
        }));
      })
      .catch((error: any) => {
        console.error('Error loading units data:', error);
        this.data = [];
      });
  }
  LoadProductsData(filter: any) {
    // tslint:disable-next-line:quotemark

    filter += ' and ProductID = ' + this.Filter.ItemID;

    this.http
      .getData(
        'qrystock?flds=ProductName,Stock,SPrice,PPrice,ProductID' +
          ' &filter=' +
          filter +
          '&orderby=ProductID'
      )
      .then((r: any) => {
        this.setting.Columns = this.colProducts;
        // Transform the data to match expected format  
        this.data = r.map((item: any) => ({
          Date: new Date().toLocaleDateString(),
          RefID: 'STK-' + item.ProductID,
          CustomerName: item.ProductName,
          QtyIn: item.Stock > 0 ? item.Stock : 0,
          QtyOut: item.Stock < 0 ? Math.abs(item.Stock) : 0,
          Balance: item.Stock
        }));
      })
      .catch((error: any) => {
        console.error('Error loading products data:', error);
        this.data = [];
      });
  }
  Clicked(e: any) {}
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title = 'Product Accounts';
    this.ps.PrintData.SubTitle =
      'From :' +
      JSON2Date(this.Filter.FromDate) +
      ' To: ' +
      JSON2Date(this.Filter.ToDate) +
      ' Product: ' +
      (this.cmbProduct ? this.cmbProduct.text : 'All');
    this.router.navigateByUrl('/print/print-html');
  }
  CustomerSelected(e: any) {}
  formatDate(d: any) {
    return JSON2Date(d);
  }
}
