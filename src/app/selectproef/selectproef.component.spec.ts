import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SelectproefComponent } from './selectproef.component';

describe('SelectproefComponent', () => {
  let component: SelectproefComponent;
  let fixture: ComponentFixture<SelectproefComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SelectproefComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SelectproefComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
