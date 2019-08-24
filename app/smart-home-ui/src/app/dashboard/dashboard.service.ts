import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError } from 'rxjs/operators';
import {LoginService} from "../login/login.service";

@Injectable({
  providedIn: 'root'
})
export class DashboardService {

  configUrl = '/webui/getDashboard';
  deviceUrl = '/webui/runFunction';

  constructor(private http: HttpClient, private loginService: LoginService) { }

  getDashboardData(){
    return this.http.get(this.configUrl);
  }

  handleError(error){
    console.log(error.error.status);
  }

  setDevice(fn){
    console.log(fn)
    return this.http.post(this.deviceUrl, {function: fn});
  }
}
