import { Component, OnInit, ViewEncapsulation, OnDestroy, OnChanges, Input } from '@angular/core';
import { FormBuilder, FormGroup, FormControl, Validators } from '@angular/forms';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { LocalStorage } from 'ngx-store';
import { IbanValidator } from './ibanvalidator';
import { AbstractControl } from '@angular/forms/src/model';
//import { Router, ActivatedRoute, ParamMap } from '@angular/router';

import { Observable } from 'rxjs/observable';
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/map';

@Component({
  selector: 'dashboard',
  templateUrl: './incassodashboard.component.html',
  styleUrls: ['./incassodashboard.component.css'],
  providers: [
    IbanValidator,
  ],
  encapsulation: ViewEncapsulation.None
})
export class IncassoDashboardComponent implements OnInit, OnDestroy {
  toernooinaam = "Niet opgestart vanuit de ToernooiAssistent!";
  incassoform : FormGroup;
  submitted = false;
  validateX : Function;
  @Input() isProef;
  formvorm;
  dedata;
  fname;
  isResultaat = false;
  fout = '';
  aantalTransacties = 0;
  totaalbedrag = 0;
  private Url = "https://www.toernooiklapper.nl/inc-ng/indexN.php";
    @LocalStorage() gegevens = {
      incassant: "",
      rekeningnr : "",
      incid : "",
      pemail : "" ,
      herhaalbaar : false}
    @LocalStorage() proefgegevens = {
      proefaccount : "",
      rekeningnrt : "",
    }
    
  constructor(private http: HttpClient, private fb: FormBuilder, private ibanvalidator : IbanValidator,
   ) {
    //this.dedata = {'gegevens' : this.gegevens}
    this.formvorm = {
      gegevens: this.fb.group({
      incassant: ['',Validators.required],
      rekeningnr: ['',{validators:[Validators.required,
        Validators.pattern(/^NL\d\d[A-Z]{4}[0-9]{10}$/i)],
        asyncValidators:this.ibanvalidator.validateIban()
        }],
      incid: ['',[Validators.required,Validators.pattern(/^NL\d\dZZZ[0-9]{12}$/i)]],
      pemail: ['',[Validators.required,Validators.email]],
      herhaalbaar: ['']
      }),
      proefgegevens: this.fb.group({
        proefaccount :  [''],
        rekeningnrt: ['',{updateOn : 'blur', validators:[Validators.required,
            Validators.pattern(/^NL\d\d[A-Z]{4}[0-9]{10}$/i)],
            asyncValidators:[this.ibanvalidator.validateIban()]}],
        })
      };
  }

  ngOnInit() {
    this.dedata = {gegevens : this.gegevens, proefgegevens : this.proefgegevens};
    this.createForm();
    console.log('++++', this.dedata);
    const body = { op : 'getparms'};
    this.http.post(this.Url, body)
      .subscribe(data => {
        console.log(data);
        if (data) {
          if (data['toernooinaam']) this.toernooinaam = data['toernooinaam'];
//        if (data['proefincasso']) this.isProef = !!data["proefincasso"];
          if (data['fname']) this.fname = data['fname'];
          if (data['aantal']) this.aantalTransacties = data['aantal']; 
          if (data['totaal']) this.totaalbedrag = data['totaal']; 
        }
      },
     (err : HttpErrorResponse) => {
       if (err.error instanceof ErrorEvent) {
         console.log('Fout opgetreden:', err.error.message)
       } else {
        console.log(`Backend returned code ${err.status}, body was ${err.error}`);
        console.log(err);
       }
     }
    );
  }
  ngOnDestroy() {}
  onSubmit()  { 
    this.submitted = true;
    //const body = Object.assign({op : 'getparms', data : this.incassoform.value}); 
    this.incassoform.value.gegevens.rekeningnr = this.incassoform.value.gegevens.rekeningnr.toUpperCase();
    this.incassoform.value.gegevens.incid = this.incassoform.value.gegevens.incid.toUpperCase();
    this.incassoform.value.proefgegevens.rekeningnrt = this.incassoform.value.proefgegevens.rekeningnrt.toUpperCase();
    this.gegevens = this.incassoform.value.gegevens;
    this.proefgegevens = this.incassoform.value.proefgegevens;
this.http.post(this.Url,  { op : 'incasso', 'isProef': this.isProef,
         'gegevens' : JSON.stringify(this.gegevens), 'proefgegevens' : JSON.stringify(this.proefgegevens)})
      .subscribe((data) => {
        this.isResultaat = true;
        this.fout = "";
        console.log(data);
      },
     (err : HttpErrorResponse) => {
       if (err.error instanceof ErrorEvent) {
         console.log('Fout opgetreden:', err.error.message);
         this.fout = `Fout opgetreden: ${err.error.message}`;
       } else {
         console.log(`Backend returned code ${err.status}`,err.error);
         this.fout = `Fout: Backend returned code ${err.status}, body was ${err.error}`;
       }
     }
    );
  }
  createForm() {
    this.incassoform = this.fb.group(this.formvorm);
    this.incassoform.setValue(
      this.dedata
    );
  }

}
