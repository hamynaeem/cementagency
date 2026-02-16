import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeadAccountsComponent } from './dead-accounts.component';

describe('DeadAccountsComponent', () => {
  let component: DeadAccountsComponent;
  let fixture: ComponentFixture<DeadAccountsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeadAccountsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DeadAccountsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});