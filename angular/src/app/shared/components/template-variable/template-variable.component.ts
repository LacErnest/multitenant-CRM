import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  Component,
  ElementRef,
  HostListener,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Renderer2,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import {
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';

@Component({
  selector: 'oz-finance-template-variable',
  templateUrl: './template-variable.component.html',
  styleUrls: ['./template-variable.component.scss'],
  animations: [
    trigger('slideOverAnimation', [
      transition(':enter', [
        style({ transform: 'translateX(100%)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(0)' })),
      ]),
      transition(':leave', [
        style({ transform: 'translateX(0)' }),
        animate('500ms ease-in-out', style({ transform: 'translateX(100%)' })),
      ]),
    ]),
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
    trigger('detailAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class TemplateVariableComponent implements OnInit, OnDestroy, OnChanges {
  @Input() disabled = false;

  public isModalVisible = false;
  public isMessageVisible = false;
  public message: string;
  public messagePosition: { top: number; left: number } = { top: 0, left: 0 };

  @ViewChild('modalElement') modalElement: ElementRef;
  private stopMouseMoveListener: any = null;
  private stopMouseUpListener: any = null;
  public dynamicVariables: { key: string; value: string }[] = [
    { key: 'Id', value: 'id' },
    { key: 'See Invoice Button', value: 'see_invoice' },
    { key: 'Project Id', value: 'project_id' },
    { key: 'Project Name', value: 'project_name' },
    { key: 'Order Id', value: 'order_id' },
    { key: 'Type', value: 'type' },
    { key: 'Date', value: 'date' },
    { key: 'Pay Date', value: 'pay_date' },
    { key: 'Due Date', value: 'due_date' },
    { key: 'Reminder days', value: 'reminder_days' },
    { key: 'Close Date', value: 'close_date' },
    { key: 'Status', value: 'status' },
    { key: 'Number', value: 'number' },
    { key: 'Reference', value: 'reference' },
    { key: 'Currency Code', value: 'currency_code' },
    { key: 'Total Price', value: 'total_price' },
    { key: 'Total Vat', value: 'total_vat' },
    { key: 'Total Price USD', value: 'total_price_usd' },
    { key: 'Total Vat USD', value: 'total_vat_usd' },
    { key: 'Total Price Customer Currency', value: 'total_price_customer_cur' },
    { key: 'Total Vat Customer Currency', value: 'total_vat_customer_cur' },
    { key: 'Total Paid Customer Currency', value: 'total_paid_customer_cur' },
    { key: 'Currency Rate Company', value: 'currency_rate_company' },
    { key: 'Currency Rate Customer', value: 'currency_rate_customer' },
    { key: 'Purchase Order Id', value: 'purchase_order_id' },
    { key: 'Manual Price', value: 'manual_price' },
    { key: 'Manual Vat', value: 'manual_vat' },
    { key: 'Legal Entity Id', value: 'legal_entity_id' },
    { key: 'VAT Status', value: 'vat_status' },
    { key: 'VAT Percentage', value: 'vat_percentage' },
    { key: 'Total Paid', value: 'total_paid' },
    { key: 'Payment Terms', value: 'payment_terms' },
    { key: 'Submitted Date', value: 'submitted_date' },
    { key: 'Created By', value: 'created_by' },
  ];

  constructor(
    private elRef: ElementRef,
    private renderer: Renderer2
  ) {}

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    const clickedInside = this.elRef.nativeElement.contains(event.target);
    if (!clickedInside) {
      this.isModalVisible = false;
    }
  }

  ngOnInit(): void {
    //
  }

  // make sure to destory the editor
  ngOnDestroy(): void {
    //
  }

  ngOnChanges(changes: SimpleChanges): void {
    //
  }

  toggleModal(): void {
    this.isModalVisible = !this.isModalVisible;
  }

  /**
   * When user clicks anywhere on the window
   */
  onBodyClick(): void {
    if (this.isModalVisible) {
      this.isModalVisible = false;
    }
  }

  /**
   * When resizing the modal window started, we store listeners
   * @param event
   */
  initResize(event: MouseEvent): void {
    event.preventDefault();
    this.stopMouseMoveListener = this.renderer.listen(
      'window',
      'mousemove',
      e => this.resize(e)
    );
    this.stopMouseUpListener = this.renderer.listen('window', 'mouseup', () =>
      this.stopResize()
    );
  }

  /**
   * When user click to resize the modal window
   * @param event
   */
  resize(event: MouseEvent): void {
    const modal = this.modalElement.nativeElement;
    const width = window.innerWidth - event.clientX;
    if (width > 400) {
      // Ensure minimum width
      this.renderer.setStyle(modal, 'width', `${width}px`);
    }
  }

  /**
   * When user cancel resizing the modal window
   */
  stopResize(): void {
    // Stop listening to the mousemove and mouseup events
    if (this.stopMouseMoveListener) {
      this.stopMouseMoveListener();
      this.stopMouseMoveListener = null;
    }
    if (this.stopMouseUpListener) {
      this.stopMouseUpListener();
      this.stopMouseUpListener = null;
    }
  }
}
