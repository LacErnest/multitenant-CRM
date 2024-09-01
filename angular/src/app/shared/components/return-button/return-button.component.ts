import { Component, OnInit } from '@angular/core';
import { RoutingService } from 'src/app/core/services/routing.service';
import { Router } from '@angular/router';

@Component({
  selector: 'oz-finance-return-button',
  templateUrl: './return-button.component.html',
  styleUrls: ['./return-button.component.scss'],
})
export class ReturnButtonComponent implements OnInit {
  public constructor(
    public routingService: RoutingService,
    private router: Router
  ) {}

  public ngOnInit(): void {}

  public return(): void {
    const lastUrl = this.routingService.getLast(true);
    const [path, params] = lastUrl.split('?');

    const paramsArr = params?.split('&');
    const queryParams = paramsArr?.reduce((prev, cur) => {
      const [key, value] = cur.split('=');
      prev[key] = value;
      return prev;
    }, {});

    if (this.routingService.getTraceLength() > 0) {
      this.router.navigate([path], { queryParams }).then();
    }
  }
}
