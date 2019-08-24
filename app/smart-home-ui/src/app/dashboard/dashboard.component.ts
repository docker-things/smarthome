import {Component, OnDestroy, OnInit} from '@angular/core';
import {DashboardService} from './dashboard.service';
import {LoginService} from '../login/login.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit, OnDestroy {

  devices;
  interval;
  isRunning = false;
  response;

  constructor(private dashboardService: DashboardService, private loginService: LoginService) {}

  ngOnInit() {
    this.getData();
    this.autoRefresh();
  }

  getData() {
    this.dashboardService.getDashboardData().subscribe(data => {
      // @ts-ignore
      const newDevices = Object.keys(data.data).map(element => {
        // @ts-ignore
        const newData = {... data.data[element], name: element};
        newData.params = Object.keys(newData.params).map(param => {
          return {name: param, value: newData.params[param]};
        });
        return newData;
      });

      if (!this.devices) {
        this.devices = newDevices;
      } else {
        if (this.devices.length !== newDevices.length) {
          this.devices = newDevices;
        } else {
          for (const index in this.devices) {
            if (JSON.stringify(this.devices[index]) !== JSON.stringify(newDevices[index])) {
              this.devices = newDevices;
              break;
            }
          }
        }
      }
    }, error => this.loginService.handleError(error));


  }

  changingSlider() {
    this.isRunning = true;
  }

  sliderChange(fn, val) {
    this.isRunning = true;
    fn = fn.replace('value', val);
    this.dashboardService.setDevice(fn).subscribe(() => this.isRunning = false, error => {
      this.isRunning = false;
      this.loginService.handleError(error);
    });
  }

  toggleFunction(fn) {
    this.isRunning = true;
    this.dashboardService.setDevice(fn).subscribe(() => this.isRunning = false, error => {
      this.isRunning = false;
      this.loginService.handleError(error);
    });
  }

  ngOnDestroy() {
    clearInterval(this.interval);
  }

  autoRefresh() {
    this.interval = setInterval(() => {
      if (!this.isRunning) {
        this.getData();
      }
    }, 5000);
  }

}
