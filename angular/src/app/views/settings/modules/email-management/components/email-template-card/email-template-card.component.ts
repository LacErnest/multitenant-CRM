import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { EmailTemplate } from '../../interfaces/email-template';
@Component({
  selector: 'oz-finance-email-template-card',
  templateUrl: './email-template-card.component.html',
  styleUrls: ['./email-template-card.component.scss'],
})
export class EmailTemplateCard implements OnInit {
  @Input() isSelected = false;
  @Input() emailTemplate: EmailTemplate;
  @Input() index: number;
  @Output() public onEmailTemplateUpdated: EventEmitter<EmailTemplate> =
    new EventEmitter<EmailTemplate>();
  public isHovering: boolean;
  public imageTemplateUri: string;

  constructor(@Inject(DOCUMENT) private _document) {}

  ngOnInit(): void {
    //
  }

  /**
   * Handle email template selection
   */
  public selectEmailTemplate(): void {
    this.onEmailTemplateUpdated.emit(this.emailTemplate);
  }
}
