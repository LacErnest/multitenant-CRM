import { DOCUMENT } from '@angular/common';
import { Component, Inject, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { Subscription } from 'rxjs';
import { filter, finalize, skip } from 'rxjs/operators';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { DatatableButtonConfig } from 'src/app/shared/classes/datatable/datatable-button-config';
import { DatatableContainerBase } from 'src/app/shared/classes/datatable/datatable-container-base';
import { DatatableMenuConfig } from 'src/app/shared/classes/datatable/datatable-menu-config';
import { DownloadModalComponent } from 'src/app/shared/components/download-modal/download-modal.component';
import { ProjectEntityEnum } from 'src/app/shared/enums/project-entity.enum';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Quote } from 'src/app/shared/interfaces/entities';
import { TablePreferencesService } from 'src/app/shared/services/table-preferences.service';
import { QuotesService } from 'src/app/views/quotes/quotes.service';
import { Helpers } from '../../../../core/classes/helpers';
import { ExportFormat } from '../../../../shared/enums/export.format';
import { SharedService } from '../../../../shared/services/shared.service';
import { TemplateModel } from '../../../../shared/interfaces/template-model';
import { AppStateService } from 'src/app/shared/services/app-state.service';

@Component({
  selector: 'oz-finance-quote-list',
  templateUrl: './quote-list.component.html',
  styleUrls: ['./quote-list.component.scss'],
})
export class QuoteListComponent
  extends DatatableContainerBase
  implements OnInit, OnDestroy
{
  @ViewChild('downloadModal', { static: false })
  public downloadModal: DownloadModalComponent;

  public buttonConfig: DatatableButtonConfig = new DatatableButtonConfig({
    delete: false,
    refresh: true,
    export: this.globalService.canExport(),
    add: this.globalService.getUserRole() !== UserRole.HUMAN_RESOURCES,
  });
  public rowMenuConfig: DatatableMenuConfig = new DatatableMenuConfig({
    delete: false,
  });
  public isLoading = false;
  public quotes: { data: any[]; count: number }; // TODO: add interface
  public projectEntity = ProjectEntityEnum;
  public templates: TemplateModel[] = [];

  private navigationSub: Subscription;
  private companySub: Subscription;
  private template_id: string;

  constructor(
    private enumService: EnumService,
    private globalService: GlobalService,
    public quotesService: QuotesService,
    protected route: ActivatedRoute,
    private router: Router,
    private routingService: RoutingService,
    protected tablePreferencesService: TablePreferencesService,
    private toastService: ToastrService,
    private sharedService: SharedService,
    protected appStateService: AppStateService,
    @Inject(DOCUMENT) private doc: Document
  ) {
    super(tablePreferencesService, route, appStateService, doc);
  }

  public ngOnInit(): void {
    this.getResolvedData();
    this.setPermissions();
    this.getCompanyTemplates();

    this.companySub = this.globalService
      .getCurrentCompanyObservable()
      .pipe(skip(1))
      .subscribe(value => {
        this.resetPaging();
        if (value?.id === 'all') {
          this.router.navigate(['/']).then();
        } else {
          this.router.navigate(['/' + value.id + '/quotes']).then();
        }
      });

    this.navigationSub = this.router.events
      .pipe(filter(e => e instanceof NavigationEnd))
      .subscribe(() => this.getResolvedData());
  }

  public ngOnDestroy(): void {
    this.navigationSub?.unsubscribe();
    this.companySub?.unsubscribe();
  }

  public addQuote(): void {
    this.routingService.setNext();
    this.router
      .navigate([`/${this.globalService.currentCompany.id}/projects/create`])
      .then();
  }

  public editQuote({ id: quoteId, project_id: projectId }): void {
    this.routingService.setNext();
    this.router
      .navigate([
        `/${this.globalService.currentCompany.id}/projects/${projectId}/quotes/${quoteId}/edit`,
      ])
      .then();
  }

  public deleteQuote(quotes: Quote[]): void {
    this.isLoading = true;

    this.quotesService
      .deleteQuotes(quotes.map(q => q.id.toString()))
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(() => {
        /*const [deletedQuote] = quotes;
        const index = this.quotes.data.findIndex(q => q.id === deletedQuote.id);
        this.quotes.data[index].status = 1;*/
        this.getData();
        const msgBeginning = quotes.length > 1 ? 'Quotes have' : 'Quote has';
        this.toastService.success(
          `${msgBeginning} been successfully deleted`,
          'Success'
        );
      });
  }

  public downloadQuote({ project_id, id, number }): void {
    this.template_id = this.templates[0]['id'];
    this.downloadModal
      .openModal(
        this.quotesService.exportQuoteCallback,
        [project_id, id, this.template_id],
        'Quote: ' + number,
        null,
        null,
        this.templates
      )
      .subscribe(
        () => {
          //
        },
        () => {
          //
        }
      );
  }

  public exportQuotes(): void {
    this.isLoading = true;
    this.quotesService
      .exportQuotes()
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

  public cloneQuote({ id, projectID, destination }): void {
    this.isLoading = true;

    this.quotesService
      .cloneQuote(projectID, id, destination)
      .pipe(
        finalize(() => {
          this.isLoading = false;
        })
      )
      .subscribe(() => {
        this.toastService.success('Quote cloned successfully', 'Success');
        this.getData();
      });
  }

  protected getData(): void {
    this.isLoading = true;
    this.checkIfAnalyticsDataFetch();

    this.quotesService
      .getQuotes(this.params)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.quotes = response;
      });
  }

  private getResolvedData(): void {
    const { quotes, table_preferences } = this.route.snapshot.data;
    this.quotes = quotes;
    this.preferences = table_preferences;
  }

  private setPermissions(): void {
    const role = this.globalService.getUserRole();

    if (
      role === UserRole.PROJECT_MANAGER ||
      role === UserRole.PROJECT_MANAGER_RESTRICTED
    ) {
      this.rowMenuConfig.edit = false;
      this.rowMenuConfig.clone = false;
      this.rowMenuConfig.export = false;
      this.rowMenuConfig.view = true;
      this.buttonConfig.add = false;
    }

    if (role === UserRole.OWNER_READ_ONLY) {
      this.rowMenuConfig.clone = false;
      this.buttonConfig.add = false;
    }
  }

  private getCompanyTemplates(): void {
    this.sharedService.getTemplates().subscribe(response => {
      this.templates = response;
    });
  }
}
