import { TestBed } from '@angular/core/testing';

import { TriggersService } from './triggers.service';

describe('TriggersService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: TriggersService = TestBed.get(TriggersService);
    expect(service).toBeTruthy();
  });
});
