import {
  Component,
  EventEmitter,
  Inject,
  Input,
  OnInit,
  Output,
  Renderer2,
} from '@angular/core';
import { Subject, Subscription } from 'rxjs';
import { DOCUMENT } from '@angular/common';
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
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
} from '../../../../shared/animations/browser-animations';
import { GlobalService } from '../../../../core/services/global.service';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import moment from 'moment';
import { skip } from 'rxjs/operators';
import { ConditionalRequiredValidator } from '../../../../shared/validators/conditional-required.validator';
import { FilterOption } from '../../containers/analytics/analytics.component';

@Component({
  selector: 'oz-finance-filter-modal',
  templateUrl: './filter-modal.component.html',
  styleUrls: ['./filter-modal.component.scss'],
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
    trigger('displayAnimation', [
      transition(':enter', useAnimation(displayAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})

// TODO: refactor class properties and methods
export class FilterModalComponent implements OnInit {
  @Input() isLoading = false;
  @Input() months: null;
  @Output() applyFiltersClicked: EventEmitter<any> = new EventEmitter<any>();

  analyticsForm: FormGroup;
  chosenDate = null;
  companySub: Subscription;
  filterTypes = ['year', 'quarter', 'month', 'week', 'date'];
  modalSubject: Subject<any>;
  quarters = [
    { key: 1, value: 'Q1' },
    { key: 2, value: 'Q2' },
    { key: 3, value: 'Q3' },
    { key: 4, value: 'Q4' },
  ];
  showFilterModal = false;
  title: string;
  years = [];
  weeks = Array.from(Array(52), (_, i) => i + 1);

  constructor(
    @Inject(DOCUMENT) private _document,
    private globalService: GlobalService,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    this.initForm();

    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(() => {
        this.analyticsForm.patchValue({
          type: 'year',
          year: new Date().getUTCFullYear(),
        });
        this.resetAnalyticsForm();
      });

    this.analyticsForm.controls.type.valueChanges.subscribe(() => {
      this.resetAnalyticsForm();
    });
  }

  ngOnDestroy() {
    if (this.showFilterModal) {
      this.closeModal();
    }

    this.companySub?.unsubscribe();
  }

  resetAnalyticsForm() {
    this.analyticsForm.controls.quarter.reset(undefined);
    this.analyticsForm.controls.month.reset(undefined);
    this.analyticsForm.controls.week.reset(undefined);
    this.analyticsForm.controls.day.reset(undefined);
  }

  public openModal(title?: string): Subject<any> {
    this.title = title;
    this.renderer.addClass(this._document.body, 'modal-opened');
    this.showFilterModal = true;
    this.modalSubject = new Subject<any>();

    const savedFilters = JSON.parse(localStorage.getItem('analytics_filters'));
    if (savedFilters) {
      this.analyticsForm.patchValue(savedFilters);

      const { type, year, month, day } = this.analyticsForm.value;
      /**
       * Setting date to datepicker
       */
      if (type === 'date') {
        const d = moment.utc();
        d.set('year', year);
        d.set('month', month - 1);
        d.set('date', day);
        this.analyticsForm.patchValue({ day: d });
      }
    }
    return this.modalSubject;
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showFilterModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showFilterModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public submit(): void {
    if (this.analyticsForm.controls.type.value === 'date') {
      const day = this.analyticsForm.get('day').value.format('D');
      this.analyticsForm.patchValue({ day });
    }

    localStorage.setItem(
      'analytics_filters',
      JSON.stringify(this.analyticsForm.value)
    );
    this.applyFiltersClicked.emit(this.analyticsForm.value);
    this.analyticsForm.patchValue({ day: this.chosenDate });
  }

  onDateChange() {
    this.chosenDate = this.analyticsForm.get('day').value;
    const year = this.analyticsForm.get('day').value.format('YYYY');
    const month = this.analyticsForm.get('day').value.format('M');
    this.analyticsForm.patchValue({ year, month });
  }

  private initForm(): void {
    this.fillYears();

    this.analyticsForm = this.fb.group({
      type: new FormControl('year'),
      year: new FormControl(new Date().getUTCFullYear()),
      quarter: new FormControl(
        undefined,
        ConditionalRequiredValidator(
          this.fieldRequiredCondition.bind(this, 'quarter')
        )
      ),
      month: new FormControl(
        undefined,
        ConditionalRequiredValidator(
          this.fieldRequiredCondition.bind(this, 'month')
        )
      ),
      week: new FormControl(
        undefined,
        ConditionalRequiredValidator(
          this.fieldRequiredCondition.bind(this, 'week')
        )
      ),
      day: new FormControl(
        undefined,
        ConditionalRequiredValidator(
          this.fieldRequiredCondition.bind(this, 'date')
        )
      ),
    });
  }

  private fieldRequiredCondition(filterOption: FilterOption): boolean {
    return this.analyticsForm?.controls?.type?.value === filterOption;
  }

  private fillYears() {
    this.years = [];
    for (let i = moment().year(); i >= 1980; i--) {
      this.years.push(i);
    }
  }
}
