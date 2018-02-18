import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'selectinc',
  template: `
    <dashboard [isProef]="false"></dashboard>
  `,
  styles: []
})
export class SelectincComponent implements OnInit {

  constructor() { }

  ngOnInit() {
  }

}
