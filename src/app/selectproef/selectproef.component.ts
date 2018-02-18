import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'selectproef',
  template: `
    <dashboard [isProef]="true"></dashboard>
  `,
  styles: []
})
export class SelectproefComponent implements OnInit {

  constructor() { }

  ngOnInit() {
  }

}
