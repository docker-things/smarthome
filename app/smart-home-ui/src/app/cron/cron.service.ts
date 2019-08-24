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
export class CronService {

  cronUrl = '/webui/getCron';
  cronSaveUrl = '/webui/setCron';

  constructor(private router: Router, private http: HttpClient) {
  }

  getCron() {
    return this.http.get(this.cronUrl);
  }

  saveCron(data) {
    return this.http.post(this.cronSaveUrl, {cron: data}, httpOptions);
  }

}
