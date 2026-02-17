import * as moment from 'moment';
export function TableSetting(cols: any[]) {
  const stngs = {
    selectMode: 'single', // single|multi
    hideHeader: false,
    hideSubHeader: false,
    mode: 'internal',
    actions: {
      columnTitle: 'Actions',
      add: false,
      edit: true,
      delete: true,
      position: 'right' // left|right
    },
    edit: {
      editButtonContent: '<i class="fa fa-pencil mr-3 text-primary"></i>',
      saveButtonContent: '<i class="fa fa-check mr-3 text-success"></i>',
      cancelButtonContent: '<i class="fa fa-times text-danger"></i>',
      confirmSave: true
    },
    add: {
      addButtonContent:
        '<h4 class="mb-1"><i class="fa fa-plus ml-3 text-success"></i></h4>',
      createButtonContent: '<i class="fa fa-check mr-3 text-success"></i>',
      cancelButtonContent: '<i class="fa fa-times text-danger"></i>'
    },
    delete: {
      deleteButtonContent: '<i class="fa fa-trash-o text-danger"></i>',
      confirmDelete: true
    },
    noDataMessage: 'No data found',
    columns: {},
    pager: {
      display: true,
      perPage: 10
    }
  };

  // tslint:disable-next-line:prefer-for-of
  for (let i = 0; i < cols.length; i++) {
    stngs.columns[cols[i].fldname] = { title: cols[i].title, type: 'string' };
  }

  return stngs;
}
export function RoundTo2(num) {
  return RoundTo(num, 2);
}
export function RoundTo(num: number, dgt: number) {
  return Math.round((num * 1 + Number.EPSILON) * Math.pow(10, dgt)) / Math.pow(10, dgt);
}

export function getCurDate() {
  try {
    const userDate = JSON.parse(localStorage.getItem('currentUser') || "{}").date;
    if (userDate) {
      const date = new Date(userDate);
      return isNaN(date.getTime()) ? new Date() : date;
    }
  } catch (e) {
    console.warn('Invalid date in localStorage:', e);
  }
  return new Date();
}

export function getCurrentTime(tim = null) {
  return (
    new Date().getHours() +
    ':' +
    (new Date().getMinutes() + 1) +
    ':' +
    new Date().getSeconds()
  );
}
export function getYMDDate(dte :any = null) {
  let d = new Date();
  if (dte) {
    d = dte;
  }
  return d.getFullYear() + '-' + pad(d.getMonth() + 1, 2,'0') + '-' +   pad(d.getDate(),2,'0') ;
}
export function getDMYDate(dte: Date | null = null) {
  let d = new Date();
  if (dte) {
    d = dte;
  }
  return d.getDate() + '-' + (1 + d.getMonth()) + '-' + d.getFullYear();
}

export function JSON2Date(d) {
  // Handle string dates (e.g., "2025-09-23")
  if (typeof d === 'string') {
    // If it's already in YYYY-MM-DD format, return it as is
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (dateRegex.test(d)) {
      return d;
    }
    // Try to parse the string as a date
    const parsedDate = new Date(d);
    if (!isNaN(parsedDate.getTime())) {
      const year = parsedDate.getFullYear();
      const month = (parsedDate.getMonth() + 1).toString().padStart(2, '0');
      const day = parsedDate.getDate().toString().padStart(2, '0');
      return year + '-' + month + '-' + day;
    }
  }
  
  // Handle null, undefined, or invalid date objects
  if (!d || typeof d !== 'object' || !d.year || !d.month || !d.day) {
    console.warn('Invalid date object passed to JSON2Date:', d);
    const defaultDate = GetDateJSON();
    d = defaultDate;
  }
  
  const month = d.month.toString().padStart(2, '0');
  const day = d.day.toString().padStart(2, '0');
  return d.year + '-' + month + '-' + day;
}

export function GetDateJSON(dte: Date|null= null) {
  let d = new Date();
  
  if (dte) {
    d = dte;
  } else {
    try {
      const userDate = JSON.parse(localStorage.getItem("currentUser") || "{}").date;
      if (userDate) {
        const parsedDate = new Date(userDate);
        if (!isNaN(parsedDate.getTime())) {
          d = parsedDate;
        }
      }
    } catch (e) {
      console.warn('Invalid date in localStorage, using current date:', e);
    }
  }
  
  // Ensure d is a valid Date object
  if (!(d instanceof Date) || isNaN(d.getTime())) {
    console.warn('Invalid date object, using current date:', d);
    d = new Date();
  }
  
  return {
    year: d.getFullYear(),
    month: d.getMonth() + 1,
    day: d.getDate()
  };
}

export function GetProps(source:any, props:any): any {
  let v:any = {};
  for(let i = 0; i<props.length; i++){
    v[props[i]] = source[props[i]];
   }
   return v;
}
export function getMonthList() {
  return [
    {
      monthno: 1,
      month: 'January'
    },
    {
      monthno: 2,
      month: 'February'
    },
    {
      monthno: 3,
      month: 'March'
    },
    {
      monthno: 4,
      month: 'April'
    },
    {
      monthno: 5,
      month: 'May'
    },
    {
      monthno: 6,
      month: 'June'
    },
    {
      monthno: 7,
      month: 'July'
    },
    {
      monthno: 8,
      month: 'August'
    },
    {
      monthno: 9,
      month: 'September'
    },
    {
      monthno: 10,
      month: 'October'
    },
    {
      monthno: 11,
      month: 'November'
    },
    {
      monthno: 12,
      month: 'December'
    }
  ];
}
export function FindTotal(data, fld) {
  const sum = data.reduce((a, b) => parseFloat(a) + parseFloat(b[fld]), 0);
  return sum;
}

export function getYears() {
  const years:any = [];
  for (
    let i = new Date().getFullYear() - 1;
    i < new Date().getFullYear() + 3;
    i++
  ) {
    years.push(i);
  }
  return years;
}

function pad(n, width, z) {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}


export function formatNumber(price, digits=0, thoSeperator=',', decSeperator='.', bdisplayprice=false) {
  var i;
  if (!price) return 0

  digits = typeof digits === "undefined" ? 2 : digits;
  bdisplayprice = typeof bdisplayprice === "undefined" ? true : bdisplayprice;
  thoSeperator = typeof thoSeperator === "undefined" ? "." : thoSeperator;
  decSeperator = typeof decSeperator === "undefined" ? "," : decSeperator;
  price = price.toString();
  var _temp = price.split(".");
  var dig = digits > 0 && typeof _temp[1] === "undefined" ? "00" : _temp[1];
  if (bdisplayprice && parseInt(dig, 10) === 0) {
    dig = "-";
  } else if (digits > 0) {
    dig = dig.toString();
    if (dig.length > digits) {
      dig = Math.round(
        parseFloat("0." + dig) * Math.pow(10, digits)
      ).toString();
    }
    for (i = dig.length; i < digits; i++) {
      dig += "0";
    }
  }
  var num = _temp[0];
  if (num == "") num = "0";
  var s = num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

  /*
  var s = "",
      ii = 0,
      last_char=(num && num[0] == '-' ? 0 : -1);
     debugger;
  for (i = num.length - 1; i > last_char; i--) {
      s = ((ii++ % 3 === 2 && num.length-1-ii-last_char > 0) ? ((i > 0) ? thoSeperator : "") : "") + num.substr(i, 1) + s;
  }
  */

  if (digits > 0) s = s + decSeperator + dig;

  //return (num[0] == '-' ? '-' : '') + s;
  return s;
}

  export function  FormatDate(date) {
      return moment(date).format('DD-MM-YYYY')
    }
