import { AfterViewInit, Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { GlobalService } from 'src/app/core/services/global.service';
import { XeroRedirectService } from 'src/app/views/xero-redirect/xero-redirect.service';

@Component({
  selector: 'oz-finance-xero-redirect',
  templateUrl: './xero-redirect.component.html',
  styleUrls: ['./xero-redirect.component.scss'],
})
export class XeroRedirectComponent implements AfterViewInit {
  private xeroLegalEntityId: string;

  public constructor(
    private route: ActivatedRoute,
    private router: Router,
    private globalService: GlobalService,
    private toastrService: ToastrService,
    private xeroRedirectService: XeroRedirectService
  ) {}

  public ngAfterViewInit(): void {
    this.checkState(
      this.route.snapshot.queryParamMap.get('state'),
      this.route.snapshot.queryParamMap.get('code')
    );
  }

  private checkState(state: string, code: string): void {
    this.xeroLegalEntityId = localStorage.getItem('xero_legal_entity_id');
    const canLinkXero =
      !!code &&
      state === localStorage.getItem('xero_state') &&
      !!this.xeroLegalEntityId;

    canLinkXero ? this.linkXero(code) : this.handleNoXeroLink();
  }

  private linkXero(code: string): void {
    this.xeroRedirectService
      .linkXero(this.xeroLegalEntityId, code)
      .pipe(finalize(() => this.goToLegalEntityXeroPage()))
      .subscribe(
        () => {
          // TODO: check if LE is updated
          XeroRedirectComponent.removeSavedLegalEntityId();
          this.toastrService.success('Xero linked successfully!', 'Success');
        },
        () => {
          this.toastrService.error(
            'Failed to link company to Xero, try again or contact an administrator',
            'Error'
          );
        }
      );
  }

  private static removeSavedLegalEntityId(): void {
    localStorage.removeItem('xero_legal_entity_id');
  }

  private handleNoXeroLink(): void {
    XeroRedirectComponent.removeSavedLegalEntityId();
    this.toastrService.error(
      'Response from Xero was malformed. Please contact an administrator.',
      'Error'
    );
    this.goToLegalEntityXeroPage();
  }

  private goToLegalEntityXeroPage(): void {
    this.router
      .navigate([`/legal_entities/${this.xeroLegalEntityId}/xero`])
      .then();
  }
}
