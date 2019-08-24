import {Injectable} from '@angular/core';
import {Router} from "@angular/router";
import {HttpClient, HttpHeaders} from "@angular/common/http";

const httpOptions = {
  headers: new HttpHeaders({
    'Access-Control-Allow-Origin': '*'
  })
};

@Injectable({
  providedIn: 'root'
})
export class DevicesService {

  devicesUrl = '/webui/getObjects';
  deviceUrl = '/webui/getObjects/name=';
  deviceSaveUrl = '/webui/setObject';
  deviceDeleteUrl = '/webui/deleteObject';

  constructor(private router: Router, private http: HttpClient) {
  }

  getDevices() {
    return this.http.get(this.devicesUrl);
  }

  getDevice(name) {
    return this.http.get(this.deviceUrl + name);
  }

  saveDevice(data) {
    return this.http.post(this.deviceSaveUrl, data, httpOptions);
  }

  deleteDevice(data) {
    return this.http.post(this.deviceDeleteUrl, data, httpOptions);
  }
}
