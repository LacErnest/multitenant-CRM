import {
  Component,
  Inject,
  Input,
  OnInit,
  Renderer2,
  ChangeDetectorRef,
} from '@angular/core';
import { concat, Observable, of, Subject } from 'rxjs';
import {
  catchError,
  debounceTime,
  distinctUntilChanged,
  filter,
  switchMap,
  tap,
  finalize,
} from 'rxjs/operators';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { DOCUMENT } from '@angular/common';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { HttpParams } from '@angular/common/http';
import { CommissionsSummary } from '../../interfaces/commissions-summary';
import { CompanySetting } from 'src/app/views/settings/interfaces/company-setting';
import { Company } from 'src/app/shared/interfaces/company';
import { GlobalService } from 'src/app/core/services/global.service';
import { OrdersService } from 'src/app/views/orders/orders.service';
import { Project } from 'src/app/views/projects/modules/project/interfaces/project';

@Component({
  selector: 'oz-finance-add-sales-commission-modal',
  templateUrl: './add-sales-commission-modal.component.html',
  styleUrls: ['./add-sales-commission-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class AddSalesCommissionModalComponent implements OnInit {
  @Input() commissionSummary: CommissionsSummary;
  @Input() commissionSettings: CompanySetting;
  @Input() project: Project;

  public showAddSalesCommissionModal = false;
  public addSalesCommissionForm: FormGroup;
  private modalSubject: Subject<any>;
  private companies: Company[];
  public isLoading = false;
  private companyToQuotes;

  salesPerson: any;
  salesPersonSelect: Observable<any[]> = new Observable<any[]>();
  salesPersonInput: Subject<string> = new Subject<string>();

  company: string;
  companySelect: Observable<any[]> = new Observable<any[]>();
  companyInput: Subject<string> = new Subject<string>();

  order: string;
  invoice: string;
  orderSelect: Observable<any[]> = new Observable<any[]>();
  orderInput: Subject<string> = new Subject<string>();
  invoices: any[];
  quote: string;
  parsed_orders: any[];
  isSelectDisabled: boolean = false;

  constructor(
    private fb: FormBuilder,
    private renderer: Renderer2,
    private suggestService: SuggestService,
    private globalService: GlobalService,
    private orderService: OrdersService,
    private cdr: ChangeDetectorRef,
    @Inject(DOCUMENT) private _document
  ) {}

  ngOnInit(): void {
    this.companies = this.globalService.companies.filter(
      company => company.id != 'all'
    );
    this.orderService
      .parsedOrders()
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.parsed_orders = response;
        if (this.project) {
          const currentCompany = this.globalService.currentCompany;
          this.company = currentCompany.id;
          this.order = this.project.order.id;
          this.isSelectDisabled = true;
          this.companySelect = concat(
            of([{ id: currentCompany.id, name: currentCompany.name }]),
            this.companySelect
          );
          this.orderSelect = concat(
            of([
              { id: this.project.order.id, name: this.project.order.number },
            ]),
            this.orderSelect
          );
          this.invoices = (this.parsed_orders[this.company] ?? [])
            .filter(q => q.order_id === this.order)
            .map(q => q.invoices)
            .flat()
            .map(q => ({ id: q.id, name: q.number, quote: q.quote_id }));
        }
      });
    this.companyToQuotes = this.commissionSummary.companies.reduce(
      (prev, curr) => {
        if (!prev[curr.id]) {
          return {
            ...prev,
            [curr.id]: curr.customers
              .map(c => c.quotes)
              .reduce((acc, val) => acc.concat(val), []),
          };
        }

        return {
          ...prev,
          [curr.id]: { ...prev[curr.id], ...curr.customers.map(c => c.quotes) },
        };
      },
      {}
    );

    this.initSalesPersonAhead();
    if (!this.project) {
      this.initCompanyAhead();
      this.initQuoteAhead();
    }
  }

  public openModal(): Subject<any> {
    this.initAddSalesCommissionForm();
    this.showAddSalesCommissionModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showAddSalesCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
    this.invoice = null;
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showAddSalesCommissionModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
    this.invoices = [];
    this.invoice = null;
  }

  public submit() {
    if (this.addSalesCommissionForm.valid) {
      this.closeModal({
        sales_person_id: this.salesPerson,
        order_id: this.order,
        invoice_id: this.invoice,
        company_id: this.company,
        quote_id: this.quote,
        commission_percentage: this.addSalesCommissionForm.get(
          'commission_percentage'
        )?.value,
      });
    }
  }

  public cannotSubmit(): boolean {
    return (
      this.addSalesCommissionForm.invalid ||
      !this.addSalesCommissionForm.dirty ||
      !this.salesPerson ||
      !this.company ||
      !this.order ||
      !this.invoice
    );
  }

  private initAddSalesCommissionForm(): void {
    this.addSalesCommissionForm = this.fb.group({
      commission_percentage: new FormControl(undefined, [
        Validators.max(this.commissionSettings.max_commission_percentage),
      ]),
    });
  }

  private initSalesPersonAhead(): void {
    this.salesPersonSelect = concat(
      of([]), // default items
      this.salesPersonInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          // loading true
        }),
        switchMap(term =>
          this.suggestService
            .suggestSalesPersons(term, { company: this.company })
            .pipe(
              catchError(() => of([])),
              tap(() => {
                // loading false
              })
            )
        )
      )
    );
  }

  private initCompanyAhead(): void {
    this.companySelect = concat(
      of([]), // default items
      this.companyInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          // loading true
        }),
        switchMap(term =>
          of(
            this.companies
              .filter(q => q.name.match(new RegExp(term, 'i')))
              .map(q => ({ id: q.id, name: q.name }))
          ).pipe(
            catchError(() => of([])),
            tap(() => {
              // loading false
            })
          )
        )
      )
    );
  }

  private initQuoteAhead(): void {
    this.orderSelect = concat(
      of([]), // default items
      this.orderInput.pipe(
        filter(t => !!t),
        debounceTime(500),
        distinctUntilChanged(),
        tap(() => {
          // loading true
        }),
        switchMap(term =>
          of(
            (this.company
              ? this.parsed_orders[this.company] ?? []
              : [].concat(...Object.values(this.parsed_orders))
            )
              .filter(q => q.order.match(new RegExp(term, 'i')))
              .map(q => ({ id: q.order_id, name: q.order }))
          ).pipe(
            catchError(() => of([])),
            tap(() => {
              // loading false
            })
          )
        )
      )
    );
  }

  public onOrderChange(): void {
    if (this.company) {
      this.invoices = (this.parsed_orders[this.company] ?? [])
        .filter(q => q.order_id === this.order)
        .map(q => q.invoices)
        .flat()
        .map(q => ({ id: q.id, name: q.number, quote: q.quote_id }));
    } else {
      this.invoices = []
        .concat(...Object.values(this.parsed_orders))
        .filter(q => q.invoice.order_id === this.order)
        .map(q => q.invoices)
        .reduce((acc, val) => acc.concat(val), [])
        .map(q => ({ id: q.id, name: q.number, quote: q.quote_id }));
    }
    this.invoice = this.invoices[0].id;
    this.quote = this.invoices[0].quote;
    this.cdr.detectChanges();
  }

  public onCompanyChange(): void {
    this.initSalesPersonAhead();
    this.initCompanyAhead();
    this.initQuoteAhead();
  }

  public onEnterKey(): void {
    this.onOrderChange();
  }
}
