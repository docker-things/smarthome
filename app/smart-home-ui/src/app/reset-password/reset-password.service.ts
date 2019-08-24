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
export class ResetPasswordService {

  changePasswordUrl = '/webui/setPassword';

  constructor(private router: Router, private http: HttpClient) { }

  reset(currentPassword:string, password: string){
    return this.http.post(this.changePasswordUrl, {password: password, currentPassword: currentPassword}, httpOptions);
  }
}
