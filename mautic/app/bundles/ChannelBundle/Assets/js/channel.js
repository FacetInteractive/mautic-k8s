Mautic.messagesOnLoad = function(container) {
    mQuery(container + ' .sortable-panel-wrapper .modal').each(function() {
      // Move modals outside of the wrapper
      mQuery(this).closest('.panel').append(mQuery(this));
    });
};

Mautic.toggleChannelFormDisplay = function (el, channel) {
    Mautic.toggleTabPublished(el);

    if (mQuery(el).val() === "1" && mQuery(el).prop('checked')) {
        mQuery(el).closest('.tab-pane').find('.message_channel_properties_' + channel).removeClass('hide')
    } else {
        mQuery(el).closest('.tab-pane').find('.message_channel_properties_' + channel).addClass('hide');
    }
};

Mautic.cancelQueuedMessageEvent = function (channelId) {
    Mautic.ajaxActionRequest('channel:cancelQueuedMessageEvent',
        {
            channelId: channelId
        }, function (response) {
            if (response.success) {
                mQuery('#queued-message-'+channelId).addClass('disabled');
                mQuery('#queued-status-'+channelId).html(Mautic.translate('mautic.message.queue.status.cancelled'));
            }
        }, false
    );
};