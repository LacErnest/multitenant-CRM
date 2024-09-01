import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { GlobalService } from 'src/app/core/services/global.service';
import { DesignTemplate } from '../../interfaces/design-template';
@Component({
  selector: 'oz-finance-design-template-card',
  templateUrl: './design-template-card.component.html',
  styleUrls: ['./design-template-card.component.scss'],
})
export class DesignTemplateCard implements OnInit {
  @Input() isSelected = false;
  @Input() designTemplate: DesignTemplate;
  @Input() index: number;
  @Output() public onDesignTemplateUpdated: EventEmitter<DesignTemplate> =
    new EventEmitter<DesignTemplate>();
  public isHovering: boolean;
  public imageTemplateUri: string;
  constructor(
    @Inject(DOCUMENT) private _document,
    private globalService: GlobalService
  ) {}

  ngOnInit(): void {
    //
  }

  /**
   * On design template selection change
   */
  public selectDesignTemplate(): void {
    this.onDesignTemplateUpdated.emit(this.designTemplate);
  }
}
