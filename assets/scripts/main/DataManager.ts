import Artisan from '../class/Artisan';
import DataBridge from '../data/DataBridge';
import TableManager from './TableManager';

export default class DataManager {
    private data: (string|string[]|boolean|number)[] = []; // TODO: Typehint OK?

    public constructor(
        private tableManager: TableManager,
    ) {
    }

    public updateQuery(newQuery: string): void {
        jQuery.ajax(DataBridge.getApiUrl(`artisans-array.json?${newQuery}`), {
            success: (newData: any, _: JQuery.Ajax.SuccessTextStatus, __: JQuery.jqXHR): void => {
                this.data = newData;

                this.tableManager.updateWith(this.data);
            },
            error: (jqXHR: JQuery.jqXHR<any>, textStatus: string, errorThrown: string): void => {
                alert('ERROR'); // TODO
            },
        });
    }

    public getArtisanByIndex(index: number): Artisan {
        const data = this.data[index];

        return new Artisan(
            data[0],
            data[1],
            data[2],
            data[3],
            data[4],
            data[5],
            data[6],
            data[7],
            data[8],
            data[9],
            data[10],
            data[11],
            data[12],
            data[13],
            data[14],
            data[15],
            data[16],
            data[17],
            data[18],
            data[19],
            data[20],
            data[21],
            data[22],
            data[23],
            data[24],
            data[25],
            data[26],
            data[27],
            data[28],
            data[29],
            data[30],
            data[31],
            data[32],
            data[33],
            data[34],
            data[35],
            data[36],
            data[37],
            data[38],
            data[39],
            data[40],
            data[41],
            data[42],
            data[43],
            data[44],
            data[45],
            data[46],
            data[47],
            data[48],
            data[49],
            data[50],
            data[51],
            data[52],
            data[53],
            data[54],
            data[55],
            data[56],
            data[57],
            data[58],
            data[59],
            data[60],
            data[61],
            data[62],
            data[63],
            data[64],
            data[65],
            data[66],
            data[67],
        );
    }
}
