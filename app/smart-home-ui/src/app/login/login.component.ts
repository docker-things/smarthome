import {Component, OnInit} from '@angular/core';
import {LoginService} from './login.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  user: string;
  password: string;

  constructor(private loginService: LoginService) {
  }

  ngOnInit() {
  }

  login($event) {
    if ($event && $event.which !== 13) {
      return;
    }
    this.loginService.login(this.user, this.password);
  }

}
