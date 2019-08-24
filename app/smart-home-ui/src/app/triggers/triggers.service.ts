import { Injectable } from '@angular/core';
import {Router} from '@angular/router';
import {HttpClient, HttpHeaders} from '@angular/common/http';

const httpOptions = {
  headers: new HttpHeaders({
    'Access-Control-Allow-Origin': '*'
  })
};

@Injectable({
  providedIn: 'root'
})
export class TriggersService {

  triggersUrl = '/webui/getTriggers';
  triggerSaveUrl = '/webui/setTrigger';
  triggerDeleteUrl = '/webui/deleteTrigger';

  constructor(private router: Router, private http: HttpClient) {
  }

  getTriggers() {
    return this.http.get(this.triggersUrl);
  }

  saveTrigger(data) {
    return this.http.post(this.triggerSaveUrl, data, httpOptions);
  }

  deleteTrigger(data) {
    return this.http.post(this.triggerDeleteUrl, data, httpOptions);
  }
}
