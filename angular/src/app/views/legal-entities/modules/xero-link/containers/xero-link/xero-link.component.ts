import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { GlobalService } from 'src/app/core/services/global.service';
import { LegalEntitiesService } from 'src/app/views/legal-entities/legal-entities.service';
import { XERO_AUTH_LINK } from 'src/app/views/legal-entities/modules/xero-link/xero-config';
import { environment } from 'src/environments/environment';
import { v4 as uuidv4 } from 'uuid';

@Component({
  selector: 'oz-finance-xero-link',
  templateUrl: './xero-link.component.html',
  styleUrls: ['./xero-link.component.scss'],
})
export class XeroLinkComponent implements OnInit {
  public isXeroLinked = false;

  public constructor(
    private globalService: GlobalService,
    private legalEntitiesService: LegalEntitiesService,
    private route: ActivatedRoute
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
  }

  public navToLink(): void {
    const state = uuidv4();
    localStorage.setItem('xero_state', state);
    localStorage.setItem(
      'xero_legal_entity_id',
      this.legalEntitiesService.legalEntityId
    );

    window.open(
      environment.internal
        ? XeroLinkComponent.getInternalXeroLink(state)
        : XeroLinkComponent.getExternalXeroLink(state),
      '_blank'
    );
  }

  private static getInternalXeroLink(state): string {
    const uri = encodeURI(
      `${XERO_AUTH_LINK}
      &state=${state}
      &response_type=code
      &approval_prompt=auto
      &redirect_uri=${environment.internalXeroRedirectLink}/xero/redirect
      &client_id=${environment.xero_client_id}`.replace(/\n\s+/g, '')
    );

    return `${environment.internalXeroRedirectLink}/
      ?url=${uri}
      &cyrex_redirect_uri=${window.location.hostname}`.replace(/\n\s+/g, '');
  }

  private static getExternalXeroLink(state): string {
    return `${XERO_AUTH_LINK}
      &state=${state}
      &response_type=code
      &approval_prompt=auto
      &redirect_uri=https://${window.location.hostname}/xero/redirect
      &client_id=${environment.xero_client_id}`.replace(/\n\s+/g, '');
  }

  private getResolvedData(): void {
    // TODO: should `xero_linked` be removed from company?
    this.isXeroLinked = this.route?.snapshot?.data?.isXeroLinked;
  }
}
