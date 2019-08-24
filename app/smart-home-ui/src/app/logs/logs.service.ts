import { Injectable } from '@angular/core';
import {Router} from "@angular/router";
import {HttpClient, HttpHeaders} from "@angular/common/http";

const httpOptions = {
  headers: new HttpHeaders({
    'Access-Control-Allow-Origin':'*'
  })
};

@Injectable({
  providedIn: 'root'
})
export class LogsService {

  logsUrl = '/webui/getLogs';

  constructor(private router: Router, private http: HttpClient) { }

  getLogs(lines){
    return this.http.get(this.logsUrl + '/lines=' + lines);
  }
}
