import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatButtonModule, MatToolbarModule, MatCheckboxModule, 
  MatProgressSpinnerModule, MatCardModule, MatCheckbox } from '@angular/material';
import { MatInputModule } from '@angular/material/input';
import { MatFormFieldModule } from '@angular/material/form-field';
import {MatDialogModule} from '@angular/material';


@NgModule({
  imports: [MatButtonModule, MatToolbarModule, MatInputModule, MatCheckboxModule, 
    MatProgressSpinnerModule, MatCardModule, MatFormFieldModule,
    MatDialogModule],

  exports: [MatButtonModule, MatToolbarModule, MatCheckboxModule,
    MatInputModule, MatProgressSpinnerModule, MatCardModule, MatFormFieldModule,
    MatDialogModule],
})
export class MaterialModule { }