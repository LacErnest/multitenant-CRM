import {
  animate,
  style,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
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
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { SortType } from '@swimlane/ngx-datatable';
import { ToastrService } from 'ngx-toastr';
import { Observable, Subject } from 'rxjs';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import {
  alertEnterAnimation,
  alertLeaveAnimation,
  displayAnimation,
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { Column } from 'src/app/shared/interfaces/table-preferences';
import { SuggestService } from 'src/app/shared/services/suggest.service';
import { UserRole } from '../../enums/user-role.enum';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { AlertType } from '../alert/alert.component';

@Component({
  selector: 'oz-finance-gridtable',
  templateUrl: './gridtable.component.html',
  styleUrls: ['./gridtable.component.scss'],
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
    trigger('alertAnimation', [
      transition(':enter', useAnimation(alertEnterAnimation)),
      transition(':leave', useAnimation(alertLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class GridtableComponent implements OnInit, OnDestroy, OnChanges {
  @Input() public title: string;
  @Input() public label: string;
  @Input() public image: string;
  @Input() public data = [];
  @Input() public isLoading = false;

  @Input() public showMessage = false;
  @Input() public messageType: AlertType;
  @Input() public messageTitle: string;
  @Input() public messageDescription: string;

  @Input() public page = 0;
  @Input() public pageSize = 10;
  @Input() public count = 0;

  @Input() public disablePaging = false;

  @Input() public minTableHeight = false;
  @Input() public reorderable = true;

  @Input() public className = '';

  @Output() public pageUpdated = new EventEmitter<number>();
  @Output() public selectionDeleted = new EventEmitter<any>();

  @Output() public addClicked = new EventEmitter<any>();
  @Output() public editClicked = new EventEmitter<any>();

  public isHovering: number;

  public typeAheads = new Map<
    string,
    {
      input: Subject<any>;
      select: Observable<any>;
      loading: boolean;
      default?: any;
    }
  >();

  public selection: any[] = [];

  public messages = {
    emptyMessage: `
      <img src="/assets/no_data.svg">
      <span class="text-xl">No results</span>
    `,
  };

  private eventDebouncer = new Subject<any>();
  private onDestroy$: Subject<void> = new Subject<void>();
  lastDataTablePage$: Observable<number>;

  public constructor(
    private globalService: GlobalService,
    private enumService: EnumService,
    private fb: FormBuilder,
    private toastService: ToastrService,
    private suggestService: SuggestService,
    private routingService: RoutingService,
    private router: Router,
    private appStateService: AppStateService
  ) {
    //
  }

  public ngOnInit(): void {
    //
  }

  public ngOnDestroy(): void {
    this.onDestroy$.next();
    this.onDestroy$.complete();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes.columns || changes.allColumns) {
      //
    }
  }

  paged(event): void {
    this.pageUpdated.emit(event.offset);
  }

  add(): void {
    this.addClicked.emit();
  }

  edit(row: any): void {
    this.editClicked.emit(row);
  }

  public isOwnerReadOnly(): boolean {
    return this.globalService.getUserRole() === UserRole.OWNER_READ_ONLY;
  }

  getViewTitle(): string {
    const lowercaseTitle = this.title.toLowerCase();
    return `View ${lowercaseTitle.endsWith('ies') ? lowercaseTitle.slice(0, -3) + 'y' : lowercaseTitle.slice(0, -1)}`;
  }
}
