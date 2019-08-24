import { Component, OnInit } from '@angular/core';
import {ResetPasswordService} from "./reset-password.service";
import {LoginService} from "../login/login.service";
import {NbToastrService} from "@nebular/theme";

@Component({
  selector: 'app-reset-password',
  templateUrl: './reset-password.component.html',
  styleUrls: ['./reset-password.component.scss']
})
export class ResetPasswordComponent implements OnInit {

  currentPassword;
  newPassword;
  password;

  constructor(private resetPasswordService: ResetPasswordService, private loginService: LoginService, private toastrService: NbToastrService) { }

  resetPassword(){
    this.resetPasswordService.reset(this.currentPassword, this.password).subscribe(data => {
      // @ts-ignore
      this.toastrService.show("Passowrd changes", "Success", { status: 'success', position: 'top-right' });
    }, error => this.loginService.handleError(error));
  }

  ngOnInit() {
  }

}
