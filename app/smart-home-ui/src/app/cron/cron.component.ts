import { Component, OnInit } from '@angular/core';
import {CronService} from '../cron/cron.service';
import {LoginService} from '../login/login.service';
import {NbToastrService} from '@nebular/theme';

@Component({
  selector: 'app-cron',
  templateUrl: './cron.component.html',
  styleUrls: ['./cron.component.scss']
})
export class CronComponent implements OnInit {

  cron;
  modules;
  possibleFunctions;
  possibleOperators;

  constructor(private cronService: CronService, private loginService: LoginService, private toastrService: NbToastrService) {
  }

  ngOnInit() {
    this.getData();
  }

  addNewContition(item, $event) {
    $event.stopPropagation();
    item.unshift({
      arg1: '',
      cond: '==',
      arg2: ''
    });
  }

  addNewCFunction(item, $event) {
    $event.stopPropagation();
    item.unshift({
      function: '',
      params: []
    });
  }

  addNewCACard() {
    this.cron.push({
      if: [],
      run: [],
      name: 'Insert name here',
      interval: 0
    });
  }

  deleteItem(index, item) {
    item.splice(index, 1);
  }

  getData() {
    this.cronService.getCron().subscribe(data => {
      // @ts-ignore
      this.cron = data.data.cron;
      // @ts-ignore
      this.possibleFunctions = data.data.possibleFunctions;
      // @ts-ignore
      this.possibleOperators = data.data.possibleOperators;
    }, error => this.loginService.handleError(error));
  }

  selectionChange(params, trigger) {
    let set = false;
    for (const index in params) {
      if (!trigger.params[index]) {
        set = true;
      }
    }

    for (const index in trigger.params) {
      if (!params[index]) {
        set = true;
      }
    }

    if (set) {
      trigger.params = {...params};
    }
  }

  saveCron(trigger) {
    this.cronService.saveCron(trigger).subscribe(() => {
      // @ts-ignore
      this.toastrService.show('Cron saved', 'Success', {status: 'success', position: 'top-right'});
    }, error => this.loginService.handleError(error));
  }


}
