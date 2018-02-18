import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { IncassodashboardComponent } from './incassodashboard.component';

describe('IncassodashboardComponent', () => {
  let component: IncassodashboardComponent;
  let fixture: ComponentFixture<IncassodashboardComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ IncassodashboardComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(IncassodashboardComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
