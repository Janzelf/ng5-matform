import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SelectincComponent } from './selectinc.component';

describe('SelectincComponent', () => {
  let component: SelectincComponent;
  let fixture: ComponentFixture<SelectincComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SelectincComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SelectincComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
