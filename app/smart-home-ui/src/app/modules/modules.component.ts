import {Component, OnInit} from '@angular/core';
import {ModulesService} from "./modules.service";
import {LoginService} from "../login/login.service";
import {NbToastrService} from "@nebular/theme";

@Component({
  selector: 'app-modules',
  templateUrl: './modules.component.html',
  styleUrls: ['./modules.component.scss']
})
export class ModulesComponent implements OnInit {
  selectedModule;
  modules;
  newModuleName;

  constructor(private modulesService: ModulesService, private loginService: LoginService, private toastrService: NbToastrService) {
  }

  ngOnInit() {
    this.getData();
  }

  getData() {
    this.modulesService.getModules().subscribe(data => {
      // @ts-ignore
      this.modules = data.data.modules;
    }, error => this.loginService.handleError(error));
  }

  loadModule(name) {
    this.modulesService.getModule(name).subscribe(data => {
      // @ts-ignore
      this.selectedModule = data.data;
    })
  }

  saveModule(name) {
    this.selectedModule = {...this.selectedModule, name: name};
    this.modulesService.saveModule(this.selectedModule).subscribe(() => {
      // @ts-ignore
      this.toastrService.show("Module saved", "Success", {status: 'success', position: 'top-right'});
    }, error => this.loginService.handleError(error));
  }

  deleteModule(name) {
    this.modulesService.deleteModule({name: name}).subscribe(() => {
      // @ts-ignore
      this.toastrService.show("Module delete", "Success", {status: 'success', position: 'top-right'});
      this.getData();
    }, error => this.loginService.handleError(error));
  }

  addModule() {
    if (!this.newModuleName) {
      // @ts-ignore
      this.toastrService.show("Please enter a name", "Error", {status: 'danger', position: 'top-right'});
      return;
    }

    this.modulesService.addModule({
      name: this.newModuleName,
      Cron: "",
      Functions: "",
      GUI: "",
      Incoming: "",
      Properties: ""
    }).subscribe(() => {
      // @ts-ignore
      this.toastrService.show("Module " + this.newModuleName + " Created", "Success", {status: 'success', position: 'top-right'});
      this.newModuleName = "";
      this.getData();
    }, error => this.loginService.handleError(error));
  }

}
