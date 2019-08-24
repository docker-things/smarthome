import { TestBed } from '@angular/core/testing';

import { CronService } from './cron.service';

describe('CronService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: CronService = TestBed.get(CronService);
    expect(service).toBeTruthy();
  });
});
