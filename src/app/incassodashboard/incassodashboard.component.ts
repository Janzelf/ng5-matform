import { Component, OnInit, ViewEncapsulation, OnDestroy, OnChanges, Input } from '@angular/core';
import { FormBuilder, FormGroup, FormControl, Validators } from '@angular/forms';
import { LocalStorage } from 'ngx-store';
import { IbanValidator } from './ibanvalidator';
import { ConnectTaService } from "./connect-ta.service";
import { AbstractControl } from '@angular/forms/src/model';

@Component({
  selector: 'dashboard',
  templateUrl: './incassodashboard.component.html',
  styleUrls: ['./incassodashboard.component.css'],
  providers: [
    IbanValidator,
    ConnectTaService
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
    
  constructor(private fb: FormBuilder, private ibanvalidator : IbanValidator,
    private connectHost: ConnectTaService )
    {
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
    this.connectHost.getParms()
      .subscribe(data => {
        if (data) {
          if (data['toernooinaam']) this.toernooinaam = data['toernooinaam'];
          if (data['fname']) this.fname = data['fname'];
          if (data['aantal']) this.aantalTransacties = data['aantal']; 
          if (data['totaal']) this.totaalbedrag = data['totaal']; 
        }
      }
    );
  }
  ngOnDestroy() {}
  onSubmit()  { 
    this.submitted = true;
    this.incassoform.value.gegevens.rekeningnr = this.incassoform.value.gegevens.rekeningnr.toUpperCase();
    this.incassoform.value.gegevens.incid = this.incassoform.value.gegevens.incid.toUpperCase();
    this.incassoform.value.proefgegevens.rekeningnrt = this.incassoform.value.proefgegevens.rekeningnrt.toUpperCase();
    this.gegevens = this.incassoform.value.gegevens; // voor de localstorage
    this.proefgegevens = this.incassoform.value.proefgegevens; // voor de localstorage
    this.connectHost.submitHost(this.isProef,JSON.stringify(this.gegevens),JSON.stringify(this.proefgegevens))
      .subscribe((data) => {
        if (data["result"]) {
          this.isResultaat = true;
          this.fout = "";
          console.log(data["result"]);
        }
      }
    );
  }
  createForm() {
    this.incassoform = this.fb.group(this.formvorm);
    this.incassoform.setValue(this.dedata);
  }

}
