import { Component, OnInit } from '@angular/core';
import {NbSortDirection, NbSortRequest, NbTreeGridDataSource, NbTreeGridDataSourceBuilder} from "@nebular/theme";
import {LogsService} from "./logs.service";
import {LoginService} from "../login/login.service";

interface TreeNode<T> {
  data: T;
  children?: TreeNode<T>[];
  expanded?: boolean;
}

interface FSEntry {
  Time: string;
  Object: string;
  Variable: string;
  Value?: number;
}

@Component({
  selector: 'app-logs',
  templateUrl: './logs.component.html',
  styleUrls: ['./logs.component.scss']
})
export class LogsComponent implements OnInit {

  customColumn = 'Time';
  defaultColumns = [ 'Object', 'Variable', 'Value' ];
  allColumns = [ this.customColumn, ...this.defaultColumns ];

  dataSource: NbTreeGridDataSource<FSEntry>;

  sortColumn: string;
  sortDirection: NbSortDirection = NbSortDirection.NONE;

  constructor(private dataSourceBuilder: NbTreeGridDataSourceBuilder<FSEntry>, private logsService: LogsService, private loginService: LoginService) {
    this.logsService.getLogs(100).subscribe(data => {
      // @ts-ignore
      let newData = data.data.map(item => {
        return {
          data: item,
          children: [],
          expanded: false
        }
      });
      this.dataSource = this.dataSourceBuilder.create(newData);
    }, error => this.loginService.handleError(error));

  }

  updateSort(sortRequest: NbSortRequest): void {
    this.sortColumn = sortRequest.column;
    this.sortDirection = sortRequest.direction;
  }

  getSortDirection(column: string): NbSortDirection {
    if (this.sortColumn === column) {
      return this.sortDirection;
    }
    return NbSortDirection.NONE;
  }

  getShowOn(index: number) {
    const minWithForMultipleColumns = 400;
    const nextColumnStep = 100;
    return minWithForMultipleColumns + (nextColumnStep * index);
  }

  ngOnInit() {
  }

}
