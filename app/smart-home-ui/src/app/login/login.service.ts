import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {NbToastrService} from '@nebular/theme';

const httpOptions = {
  headers: new HttpHeaders({
    'Access-Control-Allow-Origin': '*'
  })
};

@Injectable({
  providedIn: 'root'
})
export class LoginService {

  loginUrl = '/webui/login';

  constructor(private router: Router, private http: HttpClient, private toastrService: NbToastrService) {
  }


  login(user: string, password: string) {
    return this.http.post(this.loginUrl, {username: user, password}, httpOptions).subscribe(data => {
      localStorage.setItem('isLoggedIn', 'true');
      this.router.navigate(['/']);
      // @ts-ignore
      this.toastrService.show('Logged in', 'Success', {status: 'success', position: 'top-right'});
    }, error => this.handleError(error));
  }

  logout() {
    localStorage.removeItem('isLoggedIn');
    this.router.navigate(['/login']);
  }

  handleError(error) {
    if (error.error.message === 'You\'re not logged in!') {
      this.logout();
    }

    const message = error && error.error && error.error.message || 'Unknown Error';
    const status = error && error.error && error.error.status || 'Error';
    // @ts-ignore
    this.toastrService.show(message, status, {status: 'danger', position: 'top-right'});
  }
}
