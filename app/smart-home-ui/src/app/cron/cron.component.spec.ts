import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CronComponent } from './cron.component';

describe('CronComponent', () => {
  let component: CronComponent;
  let fixture: ComponentFixture<CronComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CronComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CronComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
