import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-editable-input',
  templateUrl: './editable-input.component.html',
  styleUrls: ['./editable-input.component.scss']
})
export class EditableInputComponent implements OnInit {

  @Input() obj;
  @Input() propertyName;
  isInEdit;

  constructor() { }

  editText($event) {
    $event.stopPropagation();
    this.isInEdit = true;
  }

  stopEdit($event) {
    $event.stopPropagation();
    if ($event.which === 13) {
      this.isInEdit = false;
    }
  }

  ngOnInit() {
  }

}
