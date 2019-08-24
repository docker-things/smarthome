import {Component, OnInit} from '@angular/core';
import {TriggersService} from '../triggers/triggers.service';
import {LoginService} from '../login/login.service';
import {NbToastrService} from '@nebular/theme';

@Component({
  selector: 'app-triggers',
  templateUrl: './triggers.component.html',
  styleUrls: ['./triggers.component.scss']
})
export class TriggersComponent implements OnInit {
  triggers;
  newTriggerName;
  modules;
  possibleFunctions;
  possibleOperators;
  possibleTriggers;
  selectedTrigger;

  constructor(private triggersService: TriggersService, private loginService: LoginService, private toastrService: NbToastrService) {
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
    this.selectedTrigger.actions.push({
      if: [],
      run: [],
      name: 'Insert name here'
    });
  }

  backToList() {
    this.selectedTrigger = null;
  }

  deleteItem(index, item) {
    item.splice(index, 1);
  }

  getData() {
    this.triggersService.getTriggers().subscribe(data => {
      // @ts-ignore
      this.triggers = data.data.triggers;
      // @ts-ignore
      this.possibleFunctions = data.data.possibleFunctions;
      // @ts-ignore
      this.possibleOperators = data.data.possibleOperators;
      // @ts-ignore
      this.possibleTriggers = data.data.possibleTriggers;
    }, error => this.loginService.handleError(error));
  }

  selectTrigger(trigger) {
    this.selectedTrigger = trigger;
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

  saveTrigger(trigger) {
    this.triggersService.saveTrigger(trigger).subscribe(() => {
      // @ts-ignore
      this.toastrService.show('Trigger saved', 'Success', {status: 'success', position: 'top-right'});
    }, error => this.loginService.handleError(error));
  }

  deleteTrigger(trigger) {
    this.triggersService.deleteTrigger({trigger}).subscribe(() => {
      // @ts-ignore
      this.toastrService.show('Trigger delete', 'Success', {status: 'success', position: 'top-right'});
      this.getData();
      this.selectedTrigger = null;
    }, error => this.loginService.handleError(error));
  }

  addTrigger() {
    if (!this.newTriggerName) {
      // @ts-ignore
      this.toastrService.show('Please enter a name', 'Error', {status: 'danger', position: 'top-right'});
      return;
    }

    this.triggers.unshift({
      trigger: this.newTriggerName,
      actions: [{
        run: [],
        if: [],
        name: 'Insert name here'
      }],
      image: 'Unknown.png'
    });
  }

}
