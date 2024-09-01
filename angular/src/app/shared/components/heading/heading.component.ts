import {
  ChangeDetectionStrategy,
  Component,
  Input,
  OnInit,
} from '@angular/core';

@Component({
  selector: 'oz-finance-heading',
  templateUrl: './heading.component.html',
  styleUrls: ['./heading.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HeadingComponent implements OnInit {
  @Input() public heading: string;
  @Input() public goBackUrl: string;

  public constructor() {}

  public ngOnInit(): void {}
}
