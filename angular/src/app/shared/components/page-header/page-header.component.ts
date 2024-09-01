import { Component, Input } from '@angular/core';

@Component({
  selector: 'oz-finance-page-header',
  templateUrl: './page-header.component.html',
  styleUrls: ['./page-header.component.scss'],
})
export class PageHeaderComponent {
  @Input() public heading = 'Page heading not defined';
  @Input() public showReturn = false;
}
