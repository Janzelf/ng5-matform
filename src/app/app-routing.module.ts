import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
//import { IncassoDashboardComponent } from './incassodashboard/incassodashboard.component';
import { SelectincComponent } from './selectinc/selectinc.component';
import { SelectproefComponent } from './selectproef/selectproef.component';
import { ContactComponent } from './contact/contact.component';

const routes: Routes = [
  {
    path: 'selectinc',
    component : SelectincComponent
  },
  {
    path: 'selectproef',
    component : SelectproefComponent
  },
  {
    path: 'help',
    component : ContactComponent
  },
  {
    path: '',
    redirectTo : 'selectproef',
    pathMatch : 'full'
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
