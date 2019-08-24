import {Component, HostListener, OnInit} from '@angular/core';
import {LoginService} from "./login/login.service";

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {
  sidebarState = 'sidebarState';
  minWidth = 600;
  windowWidth = null;
  menu = [
    {
      title: 'Dashboard',
      link: '/',
      icon: 'home-outline'
    },
    {
      title: 'Devices',
      link: ['devices'],
      icon: 'power-outline'
    },
    {
      title: 'Triggers',
      link: ['triggers'],
      icon: 'link-2-outline'
    },
    {
      title: 'Cron',
      link: ['cron'],
      icon: 'repeat-outline'
    },
    {
      title: 'Modules',
      link: ['modules'],
      icon: 'layers-outline'
    },
    {
      title: 'Logs',
      link: 'logs',
      icon: 'file-text-outline'
    },
    {
      title: 'Reset Password',
      link: ['/reset'],
      icon: 'refresh-outline'
    }
  ];

  constructor(private loginService: LoginService){}

  @HostListener('window:resize', ['$event'])
  onResize(event) {
    this.windowWidth = event.target.innerWidth;
    if (event.target.innerWidth < this.minWidth) {
      this.sidebarState = 'collapsed';
    } else {
      this.sidebarState = 'expanded';
    }
  }

  ngOnInit() {
    this.windowWidth = window.innerWidth;
    if (window.innerWidth < this.minWidth) {
      this.sidebarState = 'collapsed';
    } else {
      this.sidebarState = 'expanded';
    }
  }

  toggleCollapsed() {
    if (this.windowWidth < this.minWidth) {
      this.sidebarState = this.sidebarState === 'collapsed' ? 'expanded' : 'collapsed';
    }
  }

  collapse() {
    if (this.windowWidth < this.minWidth) {
      this.sidebarState = 'collapsed';
    }
  }

  logout() {
    this.loginService.logout();
  }
}
