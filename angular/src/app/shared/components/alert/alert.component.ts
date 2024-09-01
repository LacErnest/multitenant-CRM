import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'oz-finance-alert',
  templateUrl: './alert.component.html',
  styleUrls: ['./alert.component.scss'],
})
export class AlertComponent implements OnInit {
  @Input() type: AlertType = AlertType.INFO;
  @Input() title: string;
  @Input() description: string;

  constructor() {}

  ngOnInit(): void {}
}

export enum AlertType {
  SUCCESS = 'success',
  WARNING = 'warning',
  ERROR = 'error',
  INFO = 'info',
}
