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
export class ModulesService {

  modulesUrl = '/webui/getModules';
  moduleUrl = '/webui/getModule/name=';
  moduleSaveUrl = '/webui/setModule';
  moduleDeleteUrl = '/webui/deleteModule';
  moduleAddUrl = '/webui/addModule';

  constructor(private router: Router, private http: HttpClient) { }

  getModules(){
    return this.http.get(this.modulesUrl);
  }

  getModule(name){
    return this.http.get(this.moduleUrl + name);
  }

  saveModule(data){
    return this.http.post(this.moduleSaveUrl, data, httpOptions);
  }

  addModule(data){
    return this.http.post(this.moduleAddUrl, data, httpOptions);
  }

  deleteModule(data){
    return this.http.post(this.moduleDeleteUrl, data, httpOptions);
  }

}
