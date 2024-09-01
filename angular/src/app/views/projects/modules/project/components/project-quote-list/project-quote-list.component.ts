import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { DatatableContainerBase } from '../../../../../../shared/classes/datatable/datatable-container-base';
import { TablePreferencesService } from '../../../../../../shared/services/table-preferences.service';
import { ProjectQuoteService } from '../../services/project-quote.service';
import { finalize } from 'rxjs/operators';
import { ActivatedRoute, Router } from '@angular/router';
import { DatatableButtonConfig } from '../../../../../../shared/classes/datatable/datatable-button-config';
import { ToastrService } from 'ngx-toastr';
import { ProjectService } from '../../project.service';
import { DatatableMenuConfig } from '../../../../../../shared/classes/datatable/datatable-menu-config';
import { DeadlineModalComponent } from '../../../../../../shared/components/deadline-modal/deadline-modal.component';
import * as moment from 'moment';
import { DownloadModalComponent } from '../../../../../../shared/components/download-modal/download-modal.component';
import { QuotesService } from '../../../../../quotes/quotes.service';
import { QuoteStatusChangePayload } from '../../interfaces/quote-status-change-payload';
import { QuoteStatus } from '../../enums/quote-status.enum';
import { SharedService } from 'src/app/shared/services/shared.service';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { GlobalService } from 'src/app/core/services/global.service';
import { TemplateModel } from '../../../../../../shared/interfaces/template-model';
import { AppStateService } from 'src/app/shared/services/app-state.service';
import { Helpers } from 'src/app/core/classes/helpers';
import { ExportFormat } from 'src/app/shared/enums/export.format';
import { HttpParams } from '@angular/common/http';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'oz-finance-project-quote-list',
  templateUrl: './project-quote-list.component.html',
  styleUrls: ['./project-quote-list.component.scss'],
})
export class ProjectQuoteListComponent
  extends DatatableContainerBase
  implements OnInit
{
  @ViewChild('deadlineModal', { static: false })
  deadlineModal: DeadlineModalComponent;
  @ViewChild('downloadModal', { static: false })
  downloadModal: DownloadModalComponent;

  project: any;
  buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    columns: true,
    filters: true,
    delete: false,
    export: this.globalService.canExport(),
    add:
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER &&
      this.globalService.getUserRole() !== UserRole.HUMAN_RESOURCES &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER_RESTRICTED,
  });
  rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    order: true,
    delete: false,
    clone:
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER &&
      this.globalService.getUserRole() !== UserRole.HUMAN_RESOURCES &&
      this.globalService.getUserRole() !== UserRole.OWNER_READ_ONLY &&
      this.globalService.getUserRole() !== UserRole.PROJECT_MANAGER_RESTRICTED,
  });
  public projectEntity = ProjectEntityEnum;
  public quotes: { data: any; count: number };
  public templates: TemplateModel[] = [];
  private template_id: string;

  constructor(
    protected tablePreferencesService: TablePreferencesService,
    private projectQuoteService: ProjectQuoteService,
    private projectService: ProjectService,
    private sharedService: SharedService,
    private globalService: GlobalService,
    private router: Router,
    protected route: ActivatedRoute,
    private toastrService: ToastrService,
    private quoteService: QuotesService,
    protected appStateService: AppStateService,
    public quotesService: QuotesService,
    private toastService: ToastrService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.getCompanyTemplates();
  }

  addQuote() {
    this.router.navigate(['create'], { relativeTo: this.route }).then();
  }

  editQuote(id: string) {
    this.router.navigate([id, 'edit'], { relativeTo: this.route }).then();
  }

  createOrder({ id, date }) {
    this.deadlineModal.openModal(moment(date)).subscribe(
      result => {
        this.isLoading = true;
        this.projectQuoteService
          .createOrderFromProjectQuote(this.project.id, id, result)
          .pipe(
            finalize(() => {
              this.isLoading = false;
            })
          )
          .subscribe(response => {
            this.toastrService.success('Order created successfully', 'Success');
            this.router
              .navigate(['../orders/' + response.id], {
                relativeTo: this.route,
              })
              .then();
            this.getData();
          });
      },
      () => {}
    );
  }

  cloneQuote({ id, destination }) {
    this.isLoading = true;
    this.projectQuoteService
      .cloneProjectQuote(this.project.id, id, destination)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastrService.success('Quote cloned successfully', 'Success');
        this.getData();
      });
  }

  downloadQuote({ id, number }) {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.quoteService.exportQuoteCallback,
        [this.project.id, id, this.template_id],
        `Quote: ${number}`,
        null,
        null,
        this.templates
      )
      .subscribe(() => {
        //
      });
  }

  deleteQuotes(quotes: any[]) {
    this.isLoading = true;
    this.projectQuoteService
      .deleteProjectQuotes(
        this.project.id,
        quotes.map(q => q.id.toString())
      )
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.getData();
        const msgBeginning = quotes.length > 1 ? 'Quotes have' : 'Quote has';
        this.toastrService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public cancelQuote({ id: quoteId }): void {
    this.isLoading = true;

    const status: QuoteStatusChangePayload = {
      status: QuoteStatus.CANCELED,
    };

    this.sharedService
      .changeProjectQuoteStatus(this.project.id, quoteId, status)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(
        response => {
          this.getData();
          this.toastrService.success('Quote canceled successfully', 'Success');
        },
        error => {
          this.toastrService.error(error.error?.message, 'Update failed');
        }
      );
  }

  protected getData(): void {
    this.isLoading = true;

    this.quoteService
      .getProjectQuotes(this.project.id, this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.quotes = response;
      });
  }

  private getResolvedData() {
    this.project = this.route.snapshot.parent.parent.data.project;
    this.preferences = this.route.snapshot.data.tablePreferences;
    this.getData();
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    const readOnlyMode = [
      UserRole.SALES_PERSON,
      UserRole.PROJECT_MANAGER,
      UserRole.OWNER_READ_ONLY,
      UserRole.PROJECT_MANAGER_RESTRICTED,
    ].includes(role);

    const isProjectManager = role === UserRole.PROJECT_MANAGER;
    const isProjectManagerRestricted =
      role === UserRole.PROJECT_MANAGER_RESTRICTED;
    const isCurrentProjectManager = this.projectService.isCurrentProjectManager(
      this.project
    );

    if (this.project.order?.status > 1) {
      this.rowMenuConfig.order = false;
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.buttonConfig.add = false;
    }

    if (readOnlyMode) {
      this.buttonConfig.add =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.edit =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.view =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager) ||
        true;
      this.rowMenuConfig.delete =
        isProjectManager ||
        (isProjectManagerRestricted && isCurrentProjectManager);
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
    }

    if (role === UserRole.ADMINISTRATOR || role === UserRole.OWNER) {
      this.rowMenuConfig.cancel = true;
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }

  public exportQuotes(): void {
    this.isLoading = true;
    const params = Helpers.setParam(
      new HttpParams(),
      'project',
      this.project.id
    );
    this.quotesService
      .exportQuotes(params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(
        response => {
          const type = Helpers.getExportMIMEType(ExportFormat.XLSX);
          const file = new Blob([response], { type });
          this.createLinkForDownloading(ExportFormat.XLSX, file, 'Quotes');
        },
        error => {
          this.toastService.error(error.error?.message, 'Download failed');
        }
      );
  }
}
