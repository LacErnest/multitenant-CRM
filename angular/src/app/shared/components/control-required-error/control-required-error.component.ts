import { transition, trigger, useAnimation } from '@angular/animations';
import { Component, OnInit } from '@angular/core';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from 'src/app/shared/animations/browser-animations';

@Component({
  selector: 'oz-finance-control-required-error',
  templateUrl: './control-required-error.component.html',
  styleUrls: ['./control-required-error.component.scss'],
  animations: [
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class ControlRequiredErrorComponent implements OnInit {
  public constructor() {}

  public ngOnInit(): void {}
}
