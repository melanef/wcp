const PROTOCOL = 'wcp';

enum handlerStates {
    connecting = "connecting",
    waiting = "waiting",
    ready = "ready",
    closed = "closed",
}

enum actions {
    create = "create",
    read = "read",
    update = "update",
    delete = "delete",
    list = "list",
}

enum events {
  created = "created",
  updated = "updated",
  removed = "removed",
}

enum status {
  success = "success",
  failed = "failed",
}

type identifier = number | string;

export interface WcpEventMessage {
  event: keyof typeof events,
  entity: string,
  parentId: identifier,
  id: identifier,
  payload?: any,
}

export interface WcpResponseMessage {
  status: keyof typeof status,
  body?: any,
  message?: string,
}

export default class WcpClient {
  private readonly url: string;

  private handler: WebSocket;
  private handlerState: keyof typeof handlerStates;

  private responseHandler: CallableFunction;
  private readonly eventHandlers: Array<Array<CallableFunction>> = [];

  protected constructor(url: string) {
    this.url = url;
    this.responseHandler = null;
  }

  public async init(): Promise<void> {
    this.handler = new WebSocket(this.url, PROTOCOL);
    this.handlerState = handlerStates.connecting;
    this.handler.onmessage = (message: MessageEvent) => this.handleMessage(message);

    if (this.handler.readyState === WebSocket.OPEN) {
      this.handlerState = handlerStates.ready;
    }

    await this.waitForHandlerReady();
  }

  public close() {
    this.handler.close();
  }

  public addEventHandler(event: string, callback: CallableFunction): void {
    if (!this.eventHandlers[event]) {
      this.eventHandlers[event] = [] as Array<CallableFunction>;
    }

    this.eventHandlers[event].push(callback);
  }

  public async create(
      entity: string,
      parentId: identifier,
      payload?: any
  ): Promise<any> {
    await this.waitForHandlerReady();

    let result = {};
    this.prepareResponseHandler((response: WcpResponseMessage) => {
      result = response.body;
    });

    this.handlerState = handlerStates.waiting;
    this.handler.send(JSON.stringify({
      actions: actions.create,
      entity: entity,
      parentId: parentId,
      payload: payload
    }));

    await this.waitForHandlerReady();

    return result;
  }

  public async read(entity: string, id: identifier): Promise<any> {
    await this.waitForHandlerReady();

    let result = {};
    this.prepareResponseHandler((response: WcpResponseMessage) => {
      result = response.body;
    });

    this.handlerState = handlerStates.waiting;
    this.handler.send(JSON.stringify({
      actions: actions.read,
      entity: entity,
      id: id
    }));

    await this.waitForHandlerReady();

    return result;
  }

  public async update(
      entity: string,
      id: identifier,
      payload: any
  ): Promise<any> {
    await this.waitForHandlerReady();

    let result = {};
    this.prepareResponseHandler((response: WcpResponseMessage) => {
      result = response.body;
    });

    this.handlerState = handlerStates.waiting;
    this.handler.send(JSON.stringify({
      actions: actions.update,
      entity: entity,
      id: id,
      payload: payload
    }));

    await this.waitForHandlerReady();

    return result;
  }

  public async delete(entity: string, id: identifier): Promise<void> {
    await this.waitForHandlerReady();

    this.prepareResponseHandler();

    this.handlerState = handlerStates.waiting;
    this.handler.send(JSON.stringify({
      actions: actions.create,
      entity: entity,
      id: id,
    }));

    await this.waitForHandlerReady();
  }

  public async list(entity: string, parentId: identifier): Promise<Array<any>> {
    await this.waitForHandlerReady();

    const entities = [];
    this.prepareResponseHandler((response: WcpResponseMessage) => {
      if (!(response.body instanceof Array)) {
        throw new Error(`Invalid response: ${JSON.stringify(response.body)}`);
      }

      response.body.forEach(element => entities.push(element));
    });

    this.handlerState = handlerStates.waiting;
    this.handler.send(JSON.stringify({
      actions: actions.list,
      entity: entity,
      parentId: parentId
    }));

    await this.waitForHandlerReady();

    return entities;
  }

  private prepareResponseHandler(then?: CallableFunction) {
    this.responseHandler = async (response: WcpResponseMessage) => {
      if (response.status !== status.success) {
        this.handlerState = handlerStates.ready;
        this.responseHandler = null;

        throw new Error(response.message);
      }

      if (then) {
        await then(response.body);
      }

      this.handlerState = handlerStates.ready;
      this.responseHandler = null;
    };
  }

  private async handleMessage(message: MessageEvent) {
    const body = JSON.parse(message.data);

    if (body.status && !!this.responseHandler) {
      return await this.responseHandler(<WcpResponseMessage> body);
    }

    if (body.event && body.entity) {
      return await this.handleEvent(<WcpEventMessage>body);
    }

    this.handlerState = handlerStates.ready;
  }

  private async handleEvent(event: WcpEventMessage) {
    const eventType = `${event.event}:${event.entity}`;

    return await this.callEventHandlers(eventType, event);
  }

  private async callEventHandlers(event: string, argument: any): Promise<void> {
    if (!this.eventHandlers[event]) {
      return;
    }

    for (const callback of this.eventHandlers[event]) {
      await callback(argument);
    }
  }

  private async waitForHandlerReady(): Promise<void> {
    return new Promise(resolve => {
      const intervalTime = 50;
      const interval = setInterval(
          () => {
            if (this.handlerState === handlerStates.ready) {
              clearInterval(interval);
              resolve();
            }
            },
          intervalTime
      );
    });
  }
}