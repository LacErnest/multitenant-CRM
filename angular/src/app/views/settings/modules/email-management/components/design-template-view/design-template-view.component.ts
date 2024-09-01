import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
} from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { DesignTemplate } from '../../interfaces/design-template';
import { GlobalService } from 'src/app/core/services/global.service';

@Component({
  selector: 'oz-finance-design-template-view',
  templateUrl: './design-template-view.component.html',
  styleUrls: ['./design-template-view.component.scss'],
})
export class DesignTemplateView implements OnInit {
  @Input() designTemplate: DesignTemplate;
  @Input() index: number;
  @Output() onEdit = new EventEmitter<any>();
  @Output() onRefresh = new EventEmitter<any>();
  @Output() onSelect = new EventEmitter<any>();

  constructor(
    @Inject(DOCUMENT) private _document,
    private globalService: GlobalService
  ) {}

  ngOnInit(): void {
    //
  }

  /**
   * When we want to change the selected design template
   */
  public handleEdit(): void {
    this.triggerAction(this.onEdit);
  }

  /**
   * Refreshing template preview
   */
  public handleRefresh(): void {
    this.triggerAction(this.onRefresh);
  }

  /**
   * Changing template design
   */
  public handleSelect(): void {
    this.triggerAction(this.onSelect);
  }

  /**
   * Trigger refreshing, selecting actions
   * @param action
   * @param type
   */
  private triggerAction(action: EventEmitter<any>): void {
    if (action) {
      action.emit({ template: this.designTemplate, index: this.index });
    }
  }

  get designTemplateUpdatingUrl(): string {
    const companyId = this.globalService.currentCompany.id;
    return `/${companyId}/settings/design_templates/${this.designTemplate.id}/edit`;
  }
}
