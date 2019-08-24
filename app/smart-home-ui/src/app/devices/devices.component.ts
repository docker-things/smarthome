import {Component, OnInit} from '@angular/core';
import {DevicesService} from '../devices/devices.service';
import {LoginService} from '../login/login.service';
import {NbToastrService} from '@nebular/theme';

@Component({
  selector: 'app-devices',
  templateUrl: './devices.component.html',
  styleUrls: ['./devices.component.scss']
})
export class DevicesComponent implements OnInit {

  selectedDevice;
  devices;
  newDeviceName;
  modules;

  constructor(private devicesService: DevicesService, private loginService: LoginService, private toastrService: NbToastrService) {
  }

  ngOnInit() {
    this.getData();
  }

  getData() {
    this.devicesService.getDevices().subscribe(data => {
      // @ts-ignore
      this.devices = data.data.objects;
      // @ts-ignore
      this.modules = data.data.modules;
    }, error => this.loginService.handleError(error));
  }

  selectionChange(properties, device) {
    let set = false;
    for (const index in properties) {
      if (!device.properties[index]) {
        set = true;
      }
    }

    for (const index in device.properties) {
      if (!properties[index]) {
        set = true;
      }
    }

    if (set) {
      device.properties = properties;
    }
  }

  loadDevice(name) {
    this.devicesService.getDevice(name).subscribe(data => {
      // @ts-ignore
      this.selectedDevice = data.data;
    });
  }

  saveDevice(device) {
    this.devicesService.saveDevice(device).subscribe(() => {
      // @ts-ignore
      this.toastrService.show('Device saved', 'Success', {status: 'success', position: 'top-right'});
    }, error => this.loginService.handleError(error));
  }

  deleteDevice(name) {
    this.devicesService.deleteDevice({name}).subscribe(() => {
      // @ts-ignore
      this.toastrService.show('Device delete', 'Success', {status: 'success', position: 'top-right'});
      this.getData();
    }, error => this.loginService.handleError(error));
  }

  addDevice() {
    if (!this.newDeviceName) {
      // @ts-ignore
      this.toastrService.show('Please enter a name', 'Error', {status: 'danger', position: 'top-right'});
      return;
    }

    this.devices.unshift({
      name: this.newDeviceName,
      module: 'System',
      properties: [],
      image: 'Unknown.png'
    });
  }

}
