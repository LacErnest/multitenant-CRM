import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
} from '@angular/core';
import { FormBuilder, FormControl, FormGroup } from '@angular/forms';
import moment from 'moment';
import { animate, style, transition, trigger } from '@angular/animations';
import { ActivatedRoute, Router } from '@angular/router';
import { FilterOption } from '../../../views/dashboard/containers/analytics/analytics.component';
import { SummaryType } from '../../classes/balance-sheet/balance-sheet-base';
import { numberValidator } from '../../validators/number.validator';
import { Subscription } from 'rxjs';
import { GlobalService } from '../../../core/services/global.service';
import { skip } from 'rxjs/operators';
import { ConditionalRequiredValidator } from '../../validators/conditional-required.validator';

@Component({
  selector: 'oz-finance-balance-sheet',
  templateUrl: './balance-sheet.component.html',
  styleUrls: ['./balance-sheet.component.scss'],
  animations: [
    trigger('filterAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in', style({ opacity: 1 })),
      ]),
    ]),
    trigger('collapseAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('250ms ease-in-out', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('250ms ease-in-out', style({ opacity: 0 })),
      ]),
    ]),
  ],
})
export class BalanceSheetComponent implements OnInit, OnDestroy {
  @Input() summary: SummaryType;
  @Input() isLoading = false;
  @Input() currencyCode = 'EUR';
  @Output() filtersChanged = new EventEmitter<{
    formValue: any;
    filterOption: FilterOption;
  }>();

  currentFilter: any;
  show = true;
  days = [];
  weeks = Array.from(Array(52), (e, i) => i + 1);
  months = [
    { key: 1, value: 'January' },
    { key: 2, value: 'February' },
    { key: 3, value: 'March' },
    { key: 4, value: 'April' },
    { key: 5, value: 'May' },
    { key: 6, value: 'June' },
    { key: 7, value: 'July' },
    { key: 8, value: 'August' },
    { key: 9, value: 'September' },
    { key: 10, value: 'October' },
    { key: 11, value: 'November' },
    { key: 12, value: 'December' },
  ];
  quarters = [1, 2, 3, 4];
  years = [];

  chosenMonth: string;
  filters = [
    { key: 'year', value: 'Year' },
    { key: 'quarter', value: 'Quarter' },
    { key: 'month', value: 'Month' },
    { key: 'week', value: 'Week' },
    { key: 'date', value: 'Date' },
  ];

  filterForm: FormGroup;
  filterOption: FilterOption = 'year';
  periods = [1, 2, 3, 4];
  displayName: string;

  protected companySub: Subscription;

  constructor(
    protected fb: FormBuilder,
    protected globalService: GlobalService,
    protected route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.initFilterForm();

    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(() => this.submit());

    this.route.queryParams.subscribe(f => (this.currentFilter = f));
  }

  ngOnDestroy() {
    this.companySub?.unsubscribe();
  }

  monthChanged(month) {
    if (month) {
      const length = moment()
        .year(this.filterForm.controls.year.value)
        .month(month.key - 1)
        .daysInMonth();
      this.days = Array.from(Array(length), (e, i) => i + 1);
    } else {
      this.days = [];
      this.filterForm.controls.day.patchValue(undefined);
    }
  }

  submit() {
    if (this.filterForm.valid && !this.isLoading) {
      if (this.filterOption === 'month') {
        this.setMonth();
      }

      typeof this.filterForm.value.sp_name !== undefined
        ? (this.displayName = this.filterForm.value.sp_name)
        : this.displayName === undefined;
      this.filtersChanged.emit({
        formValue: this.filterForm.getRawValue(),
        filterOption: this.filterOption,
      });
    }
  }

  resetFilters() {
    this.filterOption = 'year';
    this.filterForm.patchValue({ year: moment.utc().year() });
    this.resetForm();
    this.submit();
  }

  resetForm() {
    this.filterForm.controls.quarter.reset(undefined);
    this.filterForm.controls.month.reset(undefined);
    this.filterForm.controls.week.reset(undefined);
    this.filterForm.controls.day.reset(undefined);
    this.filterForm.controls.periods.reset(undefined);
  }

  protected initFilterForm() {
    const { queryParams } = this.route.snapshot;
    this.currentFilter = queryParams;
    typeof this.currentFilter.sp_name !== undefined
      ? (this.displayName = this.currentFilter.sp_name)
      : this.displayName === undefined;

    this.fillYears();
    this.filterForm = this.fb.group({
      year: new FormControl(moment.utc().year()),
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
      periods: new FormControl(undefined, numberValidator(true)),
    });

    if (Object.keys(queryParams).length) {
      this.filterOption = queryParams.type;

      for (const [key, value] of Object.entries(queryParams)) {
        if (key === 'month') {
          const month = this.months.find(m => m.key === +value).key;
          this.filterForm.patchValue({ month });
        } else {
          this.filterForm.patchValue({ [key]: value });
        }
      }

      if (this.filterOption === 'month') {
        this.setMonth();
      }
    }
  }

  protected fieldRequiredCondition(filterOption: FilterOption): boolean {
    return this.filterOption === filterOption;
  }

  protected fillYears() {
    this.years = [];
    for (let i = moment().year(); i >= 1980; i--) {
      this.years.push(i);
    }
  }

  protected setMonth() {
    this.chosenMonth = this.months.find(
      m => m.key === +this.filterForm.controls.month.value
    ).value;
  }
}
