import { TestBed } from '@angular/core/testing';

import { LogsService } from './logs.service';

describe('LogsService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: LogsService = TestBed.get(LogsService);
    expect(service).toBeTruthy();
  });
});
