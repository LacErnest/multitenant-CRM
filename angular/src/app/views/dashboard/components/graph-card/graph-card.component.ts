import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
} from '@angular/core';
import { GlobalService } from 'src/app/core/services/global.service';
import { ChartType } from 'src/app/views/dashboard/chart-type.enum';
import { ChartColorScheme } from 'src/app/views/dashboard/interfaces/chart-color-scheme';
import { EntityType } from 'src/app/views/dashboard/types/analytics-entity';
import { FilterType } from 'src/app/views/dashboard/types/analytics-filter-type';
import { AnalyticsViewType } from 'src/app/views/dashboard/types/analytics-view-type';
import { takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { GraphColor } from 'src/app/shared/enums/graph-colors.enum';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'oz-finance-graph-card',
  templateUrl: './graph-card.component.html',
  styleUrls: ['./graph-card.component.scss'],
})
export class GraphCardComponent implements OnInit, OnChanges, OnDestroy {
  @Input() public analytics: any; // TODO: add type
  @Input() public chartType: ChartType;
  @Input() public entity: EntityType;
  @Input() public currentFilterType: FilterType;
  @Input() public isCompanyChosen: boolean;
  @Input() public innerWidth: number;

  // TODO: add types
  @Output() public chartClicked = new EventEmitter<any>();
  @Output() public viewClicked = new EventEmitter<any>();
  @Output() public summaryClicked = new EventEmitter<any>();

  public colorScheme: ChartColorScheme = {
    quotes: {
      domain: [
        GraphColor.FOREST_GREEN,
        GraphColor.DARK_GREY,
        GraphColor.LIGHT_GREEN,
        GraphColor.LIGHT_GREY,
      ],
    },
    invoices: {
      domain: [
        GraphColor.DARK_GREY,
        GraphColor.INDIAN_RED,
        GraphColor.FOREST_GREEN,
        GraphColor.VISTA_BLUE,
        GraphColor.MAROON,
      ],
    },
    purchase_orders: {
      domain: [
        GraphColor.RED,
        GraphColor.CHINESE_PURPLE,
        GraphColor.RUBY_RED,
        GraphColor.SCREAMIN_GREEN,
      ],
    },
    earnouts: {
      domain: [
        GraphColor.CADET_BLUE,
        GraphColor.LIGHT_GREEN,
        GraphColor.LIGHT_SKY_BLUE,
      ],
    },
    orders: {
      domain: [
        GraphColor.FOREST_GREEN,
        GraphColor.DARK_GREY,
        GraphColor.LIGHT_GREEN,
        GraphColor.LIGHT_GREY,
      ],
    },
  };

  public compareWithPrevYear = false;
  public chartData: any; // TODO: add type
  public view: any[] = [null, null]; // TODO: add type
  public userCurrency: number;
  public chartTypes = ChartType;

  private onDestroy$: Subject<void> = new Subject<void>();

  public constructor(private globalService: GlobalService) {
    this.calculateChartSizes(innerWidth);
  }

  public ngOnInit(): void {
    this.chartData = this.analytics.chosen_period.data;
    this.initSubscriptions();
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes?.analytics) {
      this.compareWithPrevYear = false;
      this.chartData = this.analytics.chosen_period.data;
    }

    if (changes?.innerWidth) {
      this.calculateChartSizes(this.innerWidth);
    }
  }

  public onCheckboxChange(showCompare: boolean): void {
    if (this.chartType === ChartType.TIMELINE) {
      this.compareTimelineData(showCompare);
      return;
    }

    /**
     * NOTE: there is a lot of items to display for month and day time periods,
     * so their data is NOT compared side by side
     */
    if (
      this.chartType === ChartType.STACKED_VERTICAL_BAR &&
      (this.currentFilterType === 'month' || this.currentFilterType === 'date')
    ) {
      this.chartData = showCompare
        ? this.analytics.year_before
        : this.analytics.chosen_period.data;
      return;
    }

    if (
      this.chartType === ChartType.STACKED_VERTICAL_BAR &&
      this.currentFilterType !== 'month' &&
      this.currentFilterType !== 'date'
    ) {
      if (showCompare) {
        const allNames = this.analytics.chosen_period.data
          .map(o => o.name)
          .reduce((res, cur) => {
            return res.concat([cur + ' prev', cur]);
          }, []);

        this.chartData = [
          ...this.analytics.chosen_period.data,
          ...this.analytics.year_before,
        ].sort((a, b) => allNames.indexOf(a.name) - allNames.indexOf(b.name));
      } else {
        this.chartData = this.analytics.chosen_period.data;
      }
    }
  }

  public onSelect(data): void {
    if (!this.isCompanyChosen) {
      return;
    }

    this.chartClicked.emit({ data, entity: this.getEntity(this.entity) });
  }

  public viewData(view: AnalyticsViewType): void {
    this.viewClicked.emit({ view, entity: this.getEntity(this.entity) });
  }

  public viewSummary(): void {
    this.summaryClicked.emit(this.entity);
  }

  // TODO: add type
  public showRevenueInTooltip(model): boolean {
    return (
      model.name === 'production_costs' ||
      model.name === 'general_costs' ||
      model.name === 'net_profit' ||
      model.name === 'gross_margin' ||
      model.name === 'costs'
    );
  }

  // TODO: add type
  public showIntraCompanyRevenueInTooltip(model): boolean {
    return (
      model.name === 'intra_company_production_costs' ||
      model.name === 'intra_company_general_costs' ||
      model.name === 'intra_company_net_profit'
    );
  }

  // TODO: add type
  public showIntraCompanyGmInTooltip(model): boolean {
    return model.name === 'general_costs' || model.name === 'net_profit';
  }

  public showRevenueVatInTooltip(model): boolean {
    return model.name === 'gross_margin' || model.name === 'costs';
  }

  public showGrossMarginInTooltip(model): boolean {
    return model.name === 'general_costs' || model.name === 'net_profit';
  }

  public showIntraCompanyGrossMarginInTooltip(model): boolean {
    return (
      model.name === 'intra_company_general_costs' ||
      model.name === 'intra_company_net_profit'
    );
  }

  private initSubscriptions(): void {
    this.globalService
      .getCurrentCompanyObservable()
      .pipe(takeUntil(this.onDestroy$))
      .subscribe(value => {
        this.userCurrency =
          this.globalService.getUserRole() === UserRole.ADMINISTRATOR
            ? environment.currency
            : this.globalService.userCurrency;
      });
  }

  private calculateChartSizes(innerWidth: number): void {
    if (innerWidth >= 768) {
      this.view = [innerWidth / 1.3 / 2, 500];
    } else {
      this.view = [innerWidth / 1.1, 500];
    }
  }

  private compareTimelineData(compare: boolean): void {
    if (compare) {
      this.chartData = [
        ...this.analytics.chosen_period.data,
        this.analytics.year_before[0],
      ];
    } else {
      this.chartData = this.analytics.chosen_period.data;
    }
  }

  private getEntity(entity: EntityType): number {
    // TODO: add TablePreferenceType usage and check
    switch (entity) {
      case 'quotes':
        return 4;
      case 'purchase_orders':
        return 7;
      case 'orders':
        return 5;
      case 'invoices':
        return 6;
    }
  }

  public getYAxisLabel(entity: string): string {
    switch (entity) {
      case 'orders':
        return 'Order';
      case 'quotes':
        return 'Quote';
      case 'invoices':
        return 'Pnl';
      default:
        return 'Revenue';
    }
  }
}
